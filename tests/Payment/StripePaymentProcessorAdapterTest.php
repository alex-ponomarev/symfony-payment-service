<?php

namespace App\Tests\Payment;

use App\Payment\Exception\PaymentFailedException;
use App\Payment\StripePaymentProcessorAdapter;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

final class StripePaymentProcessorAdapterTest extends TestCase
{
    public function testConvertsCentsAndProcessesSuccessfulPayment(): void
    {
        $paymentProcessor = $this->createMock(StripePaymentProcessor::class);
        $paymentProcessor
            ->expects($this->once())
            ->method('processPayment')
            ->with(100.0)
            ->willReturn(true);

        (new StripePaymentProcessorAdapter($paymentProcessor))->pay(10000);
    }

    public function testConvertsProcessorFailureToException(): void
    {
        $paymentProcessor = $this->createMock(StripePaymentProcessor::class);
        $paymentProcessor
            ->expects($this->once())
            ->method('processPayment')
            ->with(99.99)
            ->willReturn(false);

        $this->expectException(PaymentFailedException::class);
        $this->expectExceptionMessage('Stripe payment failed.');

        (new StripePaymentProcessorAdapter($paymentProcessor))->pay(9999);
    }
}
