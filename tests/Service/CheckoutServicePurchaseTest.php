<?php

namespace App\Tests\Service;

use App\Dto\PurchaseRequest;
use App\Entity\Product;
use App\Payment\PaymentProcessorRegistry;
use App\Repository\CouponRepository;
use App\Repository\ProductRepository;
use App\Service\CheckoutService;
use App\Tests\Payment\RecordingPaymentProcessor;
use PHPUnit\Framework\TestCase;

final class CheckoutServicePurchaseTest extends TestCase
{
    public function testUsesRequestedProcessorWithCalculatedAmount(): void
    {
        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(new Product('Test product', 10000));

        $couponRepository = $this->createMock(CouponRepository::class);
        $couponRepository
            ->expects($this->never())
            ->method('findOneByCode');

        $otherProcessor = new RecordingPaymentProcessor('other');
        $selectedProcessor = new RecordingPaymentProcessor('selected');
        $checkoutService = new CheckoutService(
            $productRepository,
            $couponRepository,
            new PaymentProcessorRegistry([$otherProcessor, $selectedProcessor]),
        );

        $checkoutService->purchase(
            new PurchaseRequest(1, 'DE123456789', 'selected'),
        );

        self::assertSame([], $otherProcessor->paidAmounts());
        self::assertSame([11900], $selectedProcessor->paidAmounts());
    }
}
