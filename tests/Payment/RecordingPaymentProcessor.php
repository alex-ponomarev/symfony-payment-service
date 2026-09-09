<?php

namespace App\Tests\Payment;

use App\Payment\PaymentProcessorInterface;

final class RecordingPaymentProcessor implements PaymentProcessorInterface
{
    /** @var list<int> */
    private array $paidAmounts = [];

    public function __construct(
        private readonly string $processorName,
    ) {
    }

    public function name(): string
    {
        return $this->processorName;
    }

    public function pay(int $amountInCents): void
    {
        $this->paidAmounts[] = $amountInCents;
    }

    /** @return list<int> */
    public function paidAmounts(): array
    {
        return $this->paidAmounts;
    }
}
