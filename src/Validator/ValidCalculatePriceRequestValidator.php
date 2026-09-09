<?php

namespace App\Validator;

use App\Dto\CalculatePriceRequest;
use App\Enum\Tax;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidCalculatePriceRequestValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidCalculatePriceRequest) {
            throw new UnexpectedTypeException($constraint, ValidCalculatePriceRequest::class);
        }

        if (!$value instanceof CalculatePriceRequest) {
            throw new UnexpectedValueException($value, CalculatePriceRequest::class);
        }

        if (!Tax::isValidTaxNumber($value->taxNumber)) {
            $this->context
                ->buildViolation($constraint->invalidTaxNumberMessage)
                ->atPath('taxNumber')
                ->addViolation();
        }
    }
}
