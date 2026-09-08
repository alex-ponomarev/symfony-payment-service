<?php

namespace App\Payment;

use App\Payment\Exception\PaymentFailedException;
use Exception;
use Systemeio\TestForCandidates\PaymentProcessor\PaypalPaymentProcessor;

final class PaypalPaymentProcessorAdapter implements PaymentProcessorInterface
{
    public function __construct(
        private readonly PaypalPaymentProcessor $paymentProcessor,
    ) {
    }

    public function name(): string
    {
        return 'paypal';
    }

    public function pay(int $amountInCents): void
    {
        try {
            $this->paymentProcessor->pay($amountInCents);
        } catch (Exception $exception) {
            throw new PaymentFailedException(
                'PayPal payment failed.',
                previous: $exception,
            );
        }
    }
}
