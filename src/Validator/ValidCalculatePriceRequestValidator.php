<?php

namespace App\Validator;

use App\Dto\CalculatePriceRequest;
use App\Enum\Tax;
use App\Exception\ApiErrorCode;
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

        if ($value->taxNumber !== '' && !Tax::isValidTaxNumber($value->taxNumber)) {
            $this->context
                ->buildViolation($constraint->invalidTaxNumberMessage)
                ->atPath('taxNumber')
                ->setCode(ApiErrorCode::InvalidTaxNumber->value)
                ->addViolation();
        }
    }
}
