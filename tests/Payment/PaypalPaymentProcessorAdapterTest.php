<?php

namespace App\Tests\Payment;

use App\Payment\Exception\PaymentFailedException;
use App\Payment\PaypalPaymentProcessorAdapter;
use Exception;
use PHPUnit\Framework\TestCase;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

final class PaypalPaymentProcessorAdapterTest extends TestCase
{
    public function testPassesAmountInCentsWithoutConversion(): void
    {
        $paymentProcessor = $this->createMock(PaypalPaymentProcessor::class);
        $paymentProcessor
            ->expects($this->once())
            ->method('pay')
            ->with(100000);

        (new PaypalPaymentProcessorAdapter($paymentProcessor))->pay(100000);
    }

    public function testWrapsProcessorException(): void
    {
        $processorException = new Exception('Payment provider error.');
        $paymentProcessor = $this->createMock(PaypalPaymentProcessor::class);
        $paymentProcessor
            ->expects($this->once())
            ->method('pay')
            ->with(100001)
            ->willThrowException($processorException);

        try {
            (new PaypalPaymentProcessorAdapter($paymentProcessor))->pay(100001);
            self::fail('PaymentFailedException was not thrown.');
        } catch (PaymentFailedException $exception) {
            self::assertSame('PayPal payment failed.', $exception->getMessage());
        }
    }
}
