<?php

namespace App\Payment\Exception;

use App\Exception\ApiErrorCode;
use App\Exception\ClientVisibleExceptionInterface;
use InvalidArgumentException;

final class UnsupportedPaymentProcessorException extends InvalidArgumentException implements ClientVisibleExceptionInterface
{
    public function __construct(string $name)
    {
        parent::__construct(sprintf('Payment processor "%s" is not supported.', $name));
    }

    public function errorCode(): ApiErrorCode
    {
        return ApiErrorCode::UnsupportedPaymentProcessor;
    }

    public function publicMessage(): string
    {
        return 'Unsupported payment processor.';
    }
}
