<?php

namespace App\Payment\Exception;

use InvalidArgumentException;

final class UnsupportedPaymentProcessorException extends InvalidArgumentException
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Payment processor "%s" is not supported.', $name));
    }
}
