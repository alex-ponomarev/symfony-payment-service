<?php

namespace App\Payment\Exception;

use App\Exception\ApiErrorCode;
use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class PaymentFailedException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public function errorCode(): ApiErrorCode
    {
        return ApiErrorCode::PaymentFailed;
    }

    public function publicMessage(): string
    {
        return 'Payment failed.';
    }
}
