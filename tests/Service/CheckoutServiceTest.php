<?php

namespace App\Tests\Service;

use App\Dto\CalculatePriceRequest;
use App\Entity\Coupon;
use App\Entity\Product;
use App\Payment\PaymentProcessorRegistry;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Service\CheckoutService;
use App\Service\Exception\CouponNotActiveException;
use App\Service\Exception\CouponNotFoundException;
use App\Service\Exception\ProductNotFoundException;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class CheckoutServiceTest extends TestCase
{
    private ProductRepository&MockObject $productRepository;
    private CouponRepository&MockObject $couponRepository;
    private CheckoutService $checkoutService;

    protected function setUp(): void
    {
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->couponRepository = $this->createMock(CouponRepository::class);
        $this->checkoutService = new CheckoutService(
            $this->productRepository,
            $this->couponRepository,
            new PaymentProcessorRegistry([]),
        );
    }

    #[DataProvider('taxCalculationProvider')]
    public function testCalculatesTaxWithoutCoupon(string $taxNumber, int $expectedPrice): void
    {
        $this->expectProduct(10000);
        $this->couponRepository
            ->expects($this->never())
            ->method('findOneByCode');

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, $taxNumber),
        );

        self::assertSame($expectedPrice, $price);
    }

    public static function taxCalculationProvider(): iterable
    {
        yield 'Germany 19%' => ['DE123456789', 11900];
        yield 'Italy 22%' => ['IT12345678900', 12200];
        yield 'Greece 24%' => ['GR123456789', 12400];
        yield 'France 20%' => ['FRAB123456789', 12000];
    }

    public function testAppliesPercentageDiscountBeforeTax(): void
    {
        $this->expectProduct(10000);
        $this->expectCoupon('D15', $this->createCoupon('D15', Coupon::TYPE_PERCENT, 15));

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'D15'),
        );

        self::assertSame(10115, $price);
    }

    public function testAppliesFixedDiscountBeforeTax(): void
    {
        $this->expectProduct(10000);
        $this->expectCoupon('F5', $this->createCoupon('F5', Coupon::TYPE_FIXED, 500));

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'F5'),
        );

        self::assertSame(11305, $price);
    }

    public function testPercentageDiscountCanReducePriceToZero(): void
    {
        $this->expectProduct(10000);
        $this->expectCoupon('P100', $this->createCoupon('P100', Coupon::TYPE_PERCENT, 100));

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'GR123456789', 'P100'),
        );

        self::assertSame(0, $price);
    }

    public function testFixedDiscountCannotReducePriceBelowZero(): void
    {
        $this->expectProduct(1000);
        $this->expectCoupon('F20', $this->createCoupon('F20', Coupon::TYPE_FIXED, 2000));

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'IT12345678900', 'F20'),
        );

        self::assertSame(0, $price);
    }

    public function testRoundsDiscountAndTaxToCents(): void
    {
        $this->expectProduct(999);
        $this->expectCoupon('D15', $this->createCoupon('D15', Coupon::TYPE_PERCENT, 15));

        $price = $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'D15'),
        );

        self::assertSame(1010, $price);
    }

    public function testThrowsWhenProductDoesNotExist(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(99)
            ->willReturn(null);
        $this->couponRepository
            ->expects($this->never())
            ->method('findOneByCode');

        $this->expectException(ProductNotFoundException::class);
        $this->expectExceptionMessage('Product with identifier "99" was not found.');

        $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(99, 'DE123456789'),
        );
    }

    public function testThrowsWhenCouponDoesNotExist(): void
    {
        $this->expectProduct(10000);
        $this->couponRepository
            ->expects($this->once())
            ->method('findOneByCode')
            ->with('missing')
            ->willReturn(null);

        $this->expectException(CouponNotFoundException::class);
        $this->expectExceptionMessage('Coupon with code "missing" was not found.');

        $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'missing'),
        );
    }

    public function testThrowsWhenCouponHasNotStarted(): void
    {
        $coupon = $this->createCoupon(
            'FUTURE',
            Coupon::TYPE_PERCENT,
            10,
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+2 days'),
        );
        $this->expectProduct(10000);
        $this->expectCoupon('FUTURE', $coupon);

        $this->expectException(CouponNotActiveException::class);
        $this->expectExceptionMessage('Coupon with code "FUTURE" is not active.');

        $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'FUTURE'),
        );
    }

    public function testThrowsWhenCouponHasExpired(): void
    {
        $coupon = $this->createCoupon(
            'EXPIRED',
            Coupon::TYPE_FIXED,
            500,
            new DateTimeImmutable('-2 days'),
            new DateTimeImmutable('-1 day'),
        );
        $this->expectProduct(10000);
        $this->expectCoupon('EXPIRED', $coupon);

        $this->expectException(CouponNotActiveException::class);
        $this->expectExceptionMessage('Coupon with code "EXPIRED" is not active.');

        $this->checkoutService->calculatePrice(
            new CalculatePriceRequest(1, 'DE123456789', 'EXPIRED'),
        );
    }

    private function expectProduct(int $priceInCents): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(new Product('Test product', $priceInCents));
    }

    private function expectCoupon(string $requestedCode, Coupon $coupon): void
    {
        $this->couponRepository
            ->expects($this->once())
            ->method('findOneByCode')
            ->with($requestedCode)
            ->willReturn($coupon);
    }

    private function createCoupon(
        string $code,
        string $type,
        int $discountValue,
        ?DateTimeImmutable $startedAt = null,
        ?DateTimeImmutable $finishAt = null,
    ): Coupon {
        return new Coupon(
            $code,
            $type,
            $discountValue,
            $startedAt ?? new DateTimeImmutable('-1 day'),
            $finishAt ?? new DateTimeImmutable('+1 day'),
        );
    }
}
