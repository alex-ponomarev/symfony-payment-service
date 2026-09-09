<?php

namespace App\Tests\Payment;

use App\Payment\Exception\UnsupportedPaymentProcessorException;
use App\Payment\PaymentProcessorRegistry;
use PHPUnit\Framework\TestCase;

final class PaymentProcessorRegistryTest extends TestCase
{
    public function testReturnsRegisteredProcessorByName(): void
    {
        $paypal = new RecordingPaymentProcessor('paypal');
        $stripe = new RecordingPaymentProcessor('stripe');
        $registry = new PaymentProcessorRegistry([$paypal, $stripe]);

        self::assertTrue($registry->supports('paypal'));
        self::assertTrue($registry->supports('stripe'));
        self::assertFalse($registry->supports('unknown'));
        self::assertSame($paypal, $registry->get('paypal'));
        self::assertSame($stripe, $registry->get('stripe'));
    }

    public function testThrowsForUnknownProcessor(): void
    {
        $registry = new PaymentProcessorRegistry([]);

        $this->expectException(UnsupportedPaymentProcessorException::class);
        $this->expectExceptionMessage('Payment processor "unknown" is not supported.');

        $registry->get('unknown');
    }

}
