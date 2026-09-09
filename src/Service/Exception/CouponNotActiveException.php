<?php

namespace App\Service\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class CouponNotActiveException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public function __construct(string $couponCode)
    {
        parent::__construct(sprintf('Coupon with code "%s" is not active.', $couponCode));
    }
}
