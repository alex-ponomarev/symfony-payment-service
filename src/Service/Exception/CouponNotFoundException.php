<?php

namespace App\Service\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class CouponNotFoundException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public const ERROR_CODE = 'coupon_not_found';

    public function __construct(string $couponCode)
    {
        parent::__construct(sprintf('Coupon with code "%s" was not found.', $couponCode));
    }

    public function errorCode(): string
    {
        return self::ERROR_CODE;
    }
}
