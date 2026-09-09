<?php

namespace App\Service\Exception;

use App\Exception\ApiErrorCode;
use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class CouponNotFoundException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public function __construct(string $couponCode)
    {
        parent::__construct(sprintf('Coupon with code "%s" was not found.', $couponCode));
    }

    public function errorCode(): ApiErrorCode
    {
        return ApiErrorCode::CouponNotFound;
    }

    public function publicMessage(): string
    {
        return $this->getMessage();
    }
}
