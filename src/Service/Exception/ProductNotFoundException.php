<?php

namespace App\Service\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class ProductNotFoundException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public const ERROR_CODE = 'product_not_found';

    public function __construct(int $productId)
    {
        parent::__construct(sprintf('Product with identifier "%d" was not found.', $productId));
    }

    public function errorCode(): string
    {
        return self::ERROR_CODE;
    }
}
