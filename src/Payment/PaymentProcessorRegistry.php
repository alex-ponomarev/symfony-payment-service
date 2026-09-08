<?php

namespace App\Payment;

use App\Payment\Exception\UnsupportedPaymentProcessorException;
use LogicException;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final class PaymentProcessorRegistry
{
    /** @var array<string, PaymentProcessorInterface> */
    private array $processors = [];

    /** @param iterable<PaymentProcessorInterface> $processors */
    public function __construct(
        #[AutowireIterator('app.payment_processor')]
        iterable $processors,
    ) {
        foreach ($processors as $processor) {
            $name = $processor->name();

            if (isset($this->processors[$name])) {
                throw new LogicException(sprintf('Payment processor "%s" is registered more than once.', $name));
            }

            $this->processors[$name] = $processor;
        }
    }

    public function get(string $name): PaymentProcessorInterface
    {
        return $this->processors[$name] ?? throw new UnsupportedPaymentProcessorException($name);
    }

    public function supports(string $name): bool
    {
        return isset($this->processors[$name]);
    }
}
