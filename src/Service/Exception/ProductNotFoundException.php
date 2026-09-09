<?php

namespace App\Service\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class ProductNotFoundException extends RuntimeException implements ClientVisibleExceptionInterface
{
    public function __construct(int $productId)
    {
        parent::__construct(sprintf('Product with identifier "%d" was not found.', $productId));
    }
}
