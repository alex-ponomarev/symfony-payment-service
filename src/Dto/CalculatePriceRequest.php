<?php

namespace App\Dto;

use App\Exception\ApiErrorCode;
use App\Validator\ValidCalculatePriceRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[ValidCalculatePriceRequest]
final readonly class CalculatePriceRequest
{
    public function __construct(
        #[Assert\Positive(
            message: "Product identifier must be greater than zero.",
            payload: ['error_code' => ApiErrorCode::InvalidProduct],
        )]
        public int $product,
        #[Assert\NotBlank(
            message: "Tax number is required.",
            payload: ['error_code' => ApiErrorCode::InvalidTaxNumber],
        )]
        public string $taxNumber,
        #[Assert\NotBlank(
            message: "Coupon code cannot be blank.",
            allowNull: true,
            payload: ['error_code' => ApiErrorCode::InvalidCouponCode],
        )]
        #[Assert\Length(
            max: 100,
            maxMessage: "Coupon code cannot exceed {{ limit }} characters.",
            payload: ['error_code' => ApiErrorCode::InvalidCouponCode],
        )]
        #[Assert\Regex(
            pattern: "/^[A-Za-z0-9]+$/",
            message: "Coupon code must contain only letters and digits.",
            payload: ['error_code' => ApiErrorCode::InvalidCouponCode],
        )]
        public ?string $couponCode = null,
    ) {
    }
}
