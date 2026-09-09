<?php

namespace App\Payment\Exception;

use App\Exception\ClientVisibleExceptionInterface;
use RuntimeException;

final class PaymentFailedException extends RuntimeException implements ClientVisibleExceptionInterface
{
}
