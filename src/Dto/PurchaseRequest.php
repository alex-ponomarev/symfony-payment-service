<?php

namespace App\Dto;

use App\Validator\ValidPurchaseRequest;
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\GroupSequence(['PurchaseRequest', 'external'])]
#[ValidPurchaseRequest(groups: ['external'])]
final readonly class PurchaseRequest
{
    public function __construct(
        #[Assert\Positive(message: "Product identifier must be greater than zero.")]
        public int $product,
        #[Assert\NotBlank(message: "Tax number is required.")]
        public string $taxNumber,
        #[Assert\NotBlank(message: "Payment processor is required.")]
        public string $paymentProcessor,
        #[Assert\NotBlank(
            message: "Coupon code cannot be blank.",
            allowNull: true,
        )]
        #[Assert\Length(
            max: 100,
            maxMessage: "Coupon code cannot exceed {{ limit }} characters.",
        )]
        #[Assert\Regex(
            pattern: "/^[A-Za-z0-9]+$/",
            message: "Coupon code must contain only letters and digits.",
        )]
        public ?string $couponCode = null,
    ) {
    }
}
