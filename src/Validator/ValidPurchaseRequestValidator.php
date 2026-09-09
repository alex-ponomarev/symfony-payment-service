<?php

namespace App\Validator;

use App\Dto\PurchaseRequest;
use App\Enum\Tax;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class ValidPurchaseRequestValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidPurchaseRequest) {
            throw new UnexpectedTypeException($constraint, ValidPurchaseRequest::class);
        }

        if (!$value instanceof PurchaseRequest) {
            throw new UnexpectedValueException($value, PurchaseRequest::class);
        }

        if (!Tax::isValidTaxNumber($value->taxNumber)) {
            $this->context
                ->buildViolation($constraint->invalidTaxNumberMessage)
                ->atPath('taxNumber')
                ->addViolation();
        }
    }
}
