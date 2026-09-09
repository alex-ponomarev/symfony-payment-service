<?php

namespace App\Validator;

use Attribute;
use Symfony\Component\Validator\Constraint;

#[Attribute(Attribute::TARGET_CLASS)]
final class ValidPurchaseRequest extends Constraint
{
    public string $invalidTaxNumberMessage = "Invalid tax number.";
    public string $unsupportedPaymentProcessorMessage = "Unsupported payment processor.";

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
