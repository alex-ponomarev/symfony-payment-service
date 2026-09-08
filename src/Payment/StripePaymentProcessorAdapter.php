<?php

namespace App\Payment;

use App\Payment\Exception\PaymentFailedException;
use Systemeio\TestForCandidates\PaymentProcessor\StripePaymentProcessor;

final class StripePaymentProcessorAdapter implements PaymentProcessorInterface
{
    public function __construct(
        private readonly StripePaymentProcessor $paymentProcessor,
    ) {
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function pay(int $amountInCents): void
    {
        $amount = (float) $amountInCents / 100;

        if (!$this->paymentProcessor->processPayment($amount)) {
            throw new PaymentFailedException('Stripe payment failed.');
        }
    }
}
