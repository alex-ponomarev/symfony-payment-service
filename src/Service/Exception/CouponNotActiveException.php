<?php

namespace App\Service\Exception;

use App\Exception\ApiErrorCode;
use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class CouponNotActiveException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public function __construct(string $couponCode)
    {
        parent::__construct(sprintf('Coupon with code "%s" is not active.', $couponCode));
    }

    public function errorCode(): ApiErrorCode
    {
        return ApiErrorCode::CouponNotActive;
    }

    public function publicMessage(): string
    {
        return $this->getMessage();
    }
}
