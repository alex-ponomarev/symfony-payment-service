<?php

namespace App\Payment;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.payment_processor')]
interface PaymentProcessorInterface
{
    public function name(): string;

    public function pay(int $amountInCents): void;
}
