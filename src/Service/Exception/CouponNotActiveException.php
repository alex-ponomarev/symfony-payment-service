<?php

namespace App\Service\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class CouponNotActiveException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public const ERROR_CODE = 'coupon_not_active';

    public function __construct(string $couponCode)
    {
        parent::__construct(sprintf('Coupon with code "%s" is not active.', $couponCode));
    }

    public function errorCode(): string
    {
        return self::ERROR_CODE;
    }
}
