<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
#[UniqueEntity(
    fields: ["code"],
    message: "Coupon with this code already exists.",
)]
class Coupon
{
    public const TYPE_PERCENT = "percent";
    public const TYPE_FIXED = "fixed";

    private const ALLOWED_TYPES = [
        self::TYPE_PERCENT,
        self::TYPE_FIXED,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: "Coupon code is required.")]
    #[Assert\Length(
        max: 100,
        maxMessage: "Coupon code cannot exceed {{ limit }} characters.",
    )]
    #[Assert\Regex(
        pattern: "/^[A-Z0-9]+$/",
        message: "Coupon code must contain only uppercase letters and digits.",
    )]
    private string $code;

    #[ORM\Column(length: 16)]
    #[Assert\NotBlank(message: "Coupon type is required.")]
    #[Assert\Choice(
        choices: self::ALLOWED_TYPES,
        message: "Unsupported coupon type.",
    )]
    private string $type;

    #[ORM\Column]
    #[Assert\Positive(message: "Discount value must be greater than zero.")]
    #[Assert\When(
        expression: "this.getType() == \"percent\"",
        constraints: [
            new Assert\LessThanOrEqual(
                value: 100,
                message: "Percentage discount cannot exceed 100.",
            ),
        ],
    )]
    private int $discountValue;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column]
    #[Assert\GreaterThan(
        propertyPath: "startedAt",
        message: "Coupon finish date must be later than its start date.",
    )]
    private \DateTimeImmutable $finishAt;

    public function __construct(
        string $code,
        string $type,
        int $discountValue,
        \DateTimeImmutable $startedAt,
        \DateTimeImmutable $finishAt,
    ) {
        $this->code = $code;
        $this->type = $type;
        $this->discountValue = $discountValue;
        $this->createdAt = new \DateTimeImmutable();
        $this->startedAt = $startedAt;
        $this->finishAt = $finishAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDiscountValue(): int
    {
        return $this->discountValue;
    }

    public function setDiscountValue(int $discountValue): static
    {
        $this->discountValue = $discountValue;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getStartedAt(): \DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishAt(): \DateTimeImmutable
    {
        return $this->finishAt;
    }

    public function setFinishAt(\DateTimeImmutable $finishAt): static
    {
        $this->finishAt = $finishAt;

        return $this;
    }
}
