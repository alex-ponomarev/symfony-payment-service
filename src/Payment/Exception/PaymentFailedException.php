<?php

namespace App\Payment\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class PaymentFailedException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public const ERROR_CODE = 'payment_failed';

    public function errorCode(): string
    {
        return self::ERROR_CODE;
    }
}
