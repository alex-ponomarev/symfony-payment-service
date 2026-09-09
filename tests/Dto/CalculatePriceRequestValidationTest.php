<?php

namespace App\Tests\Dto;

use App\Dto\CalculatePriceRequest;
use App\Exception\ApiErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CalculatePriceRequestValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    #[DataProvider('validRequestProvider')]
    public function testValidRequestHasNoViolations(CalculatePriceRequest $request): void
    {
        self::assertCount(0, $this->validator->validate($request));
    }

    public static function validRequestProvider(): iterable
    {
        yield 'without coupon' => [
            new CalculatePriceRequest(1, 'DE123456789'),
        ];
        yield 'with uppercase coupon' => [
            new CalculatePriceRequest(1, 'IT12345678900', 'D15'),
        ];
        yield 'with lowercase coupon' => [
            new CalculatePriceRequest(1, 'GR123456789', 'd15'),
        ];
    }

    #[DataProvider('invalidRequestProvider')]
    public function testInvalidRequestHasExpectedViolation(
        CalculatePriceRequest $request,
        string $expectedPath,
        string $expectedMessage,
        ApiErrorCode $expectedCode,
    ): void {
        $violations = $this->validator->validate($request);

        self::assertCount(1, $violations);
        self::assertSame($expectedPath, $violations[0]->getPropertyPath());
        self::assertSame($expectedMessage, $violations[0]->getMessage());
        self::assertSame($expectedCode, $this->publicErrorCode($violations[0]));
    }

    public static function invalidRequestProvider(): iterable
    {
        yield 'non-positive product' => [
            new CalculatePriceRequest(0, 'DE123456789'),
            'product',
            'Product identifier must be greater than zero.',
            ApiErrorCode::InvalidProduct,
        ];
        yield 'blank tax number' => [
            new CalculatePriceRequest(1, ''),
            'taxNumber',
            'Tax number is required.',
            ApiErrorCode::InvalidTaxNumber,
        ];
        yield 'invalid tax number' => [
            new CalculatePriceRequest(1, 'DE123'),
            'taxNumber',
            'Invalid tax number.',
            ApiErrorCode::InvalidTaxNumber,
        ];
        yield 'blank coupon code' => [
            new CalculatePriceRequest(1, 'DE123456789', ''),
            'couponCode',
            'Coupon code cannot be blank.',
            ApiErrorCode::InvalidCouponCode,
        ];
        yield 'coupon code is too long' => [
            new CalculatePriceRequest(1, 'DE123456789', str_repeat('A', 101)),
            'couponCode',
            'Coupon code cannot exceed 100 characters.',
            ApiErrorCode::InvalidCouponCode,
        ];
        yield 'coupon code contains invalid characters' => [
            new CalculatePriceRequest(1, 'DE123456789', 'D-15'),
            'couponCode',
            'Coupon code must contain only letters and digits.',
            ApiErrorCode::InvalidCouponCode,
        ];
    }

    private function publicErrorCode(ConstraintViolationInterface $violation): ?ApiErrorCode
    {
        $violationCode = $violation->getCode();

        if ($violationCode !== null && ($code = ApiErrorCode::tryFrom($violationCode)) !== null) {
            return $code;
        }

        $payload = $violation->getConstraint()?->payload;
        $payloadCode = is_array($payload) ? ($payload['error_code'] ?? null) : null;

        return $payloadCode instanceof ApiErrorCode ? $payloadCode : null;
    }
}
