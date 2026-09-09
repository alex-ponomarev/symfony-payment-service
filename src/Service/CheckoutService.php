<?php

namespace App\Service;

use App\Dto;
use App\Entity;
use App\Enum;
use App\Payment;
use App\Repository;
use DateTimeImmutable;
use LogicException;

final class CheckoutService
{
    public function __construct(
        private readonly Repository\ProductRepository $productRepository,
        private readonly Repository\CouponRepository $couponRepository,
        private readonly Payment\PaymentProcessorRegistry $paymentProcessorRegistry,
    ) {
    }

    public function calculatePrice(Dto\CalculatePriceRequest $request): int
    {
        return $this->calculate(
            $request->product,
            $request->taxNumber,
            $request->couponCode,
        );
    }

    public function purchase(Dto\PurchaseRequest $request): void
    {
        $amountInCents = $this->calculate(
            $request->product,
            $request->taxNumber,
            $request->couponCode,
        );

        $this->paymentProcessorRegistry
            ->get($request->paymentProcessor)
            ->pay($amountInCents);
    }

    private function calculate(
        int $productId,
        string $taxNumber,
        ?string $couponCode,
    ): int
    {
        $product = $this->productRepository->find($productId)
            ?? throw new Exception\ProductNotFoundException($productId);

        $priceInCents = $product->getPrice();

        if ($couponCode !== null) {
            $coupon = $this->couponRepository->findOneByCode($couponCode)
                ?? throw new Exception\CouponNotFoundException($couponCode);

            $this->assertCouponIsActive($coupon);
            $priceInCents -= $this->calculateDiscount($priceInCents, $coupon);
        }

        $taxRate = Enum\Tax::rateByTaxNumber($taxNumber)
            ?? throw new LogicException('Tax number must be validated before price calculation.');
        $taxInCents = (int) round(
            $priceInCents * $taxRate / 100,
            mode: PHP_ROUND_HALF_UP,
        );

        return $priceInCents + $taxInCents;
    }

    private function calculateDiscount(int $priceInCents, Entity\Coupon $coupon): int
    {
        $discountInCents = match ($coupon->getType()) {
            Entity\Coupon::TYPE_PERCENT => (int) round(
                $priceInCents * $coupon->getDiscountValue() / 100,
                mode: PHP_ROUND_HALF_UP,
            ),
            Entity\Coupon::TYPE_FIXED => $coupon->getDiscountValue(),
            default => throw new LogicException(sprintf(
                'Unsupported coupon type "%s".',
                $coupon->getType(),
            )),
        };

        return min($priceInCents, $discountInCents);
    }

    private function assertCouponIsActive(Entity\Coupon $coupon): void
    {
        $now = new DateTimeImmutable();

        if ($coupon->getStartedAt() > $now || $coupon->getFinishAt() <= $now) {
            throw new Exception\CouponNotActiveException($coupon->getCode());
        }
    }
}
