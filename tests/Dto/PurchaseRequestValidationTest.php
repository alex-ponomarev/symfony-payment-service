<?php

namespace App\Tests\Dto;

use App\Dto\PurchaseRequest;
use App\Exception\ApiErrorCode;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class PurchaseRequestValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    #[DataProvider('validRequestProvider')]
    public function testValidRequestHasNoViolations(PurchaseRequest $request): void
    {
        self::assertCount(0, $this->validator->validate($request));
    }

    public static function validRequestProvider(): iterable
    {
        yield 'PayPal without coupon' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal'),
        ];
        yield 'Stripe with uppercase coupon' => [
            new PurchaseRequest(1, 'IT12345678900', 'stripe', 'D15'),
        ];
        yield 'PayPal with lowercase coupon' => [
            new PurchaseRequest(1, 'FRAB123456789', 'paypal', 'd15'),
        ];
    }

    #[DataProvider('invalidRequestProvider')]
    public function testInvalidRequestHasExpectedViolation(
        PurchaseRequest $request,
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
            new PurchaseRequest(0, 'DE123456789', 'paypal'),
            'product',
            'Product identifier must be greater than zero.',
            ApiErrorCode::InvalidProduct,
        ];
        yield 'blank tax number' => [
            new PurchaseRequest(1, '', 'paypal'),
            'taxNumber',
            'Tax number is required.',
            ApiErrorCode::InvalidTaxNumber,
        ];
        yield 'invalid tax number' => [
            new PurchaseRequest(1, 'DE123', 'paypal'),
            'taxNumber',
            'Invalid tax number.',
            ApiErrorCode::InvalidTaxNumber,
        ];
        yield 'blank payment processor' => [
            new PurchaseRequest(1, 'DE123456789', ''),
            'paymentProcessor',
            'Payment processor is required.',
            ApiErrorCode::InvalidPaymentProcessor,
        ];
        yield 'unsupported payment processor' => [
            new PurchaseRequest(1, 'DE123456789', 'unknown'),
            'paymentProcessor',
            'Unsupported payment processor.',
            ApiErrorCode::UnsupportedPaymentProcessor,
        ];
        yield 'blank coupon code' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', ''),
            'couponCode',
            'Coupon code cannot be blank.',
            ApiErrorCode::InvalidCouponCode,
        ];
        yield 'coupon code is too long' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', str_repeat('A', 101)),
            'couponCode',
            'Coupon code cannot exceed 100 characters.',
            ApiErrorCode::InvalidCouponCode,
        ];
        yield 'coupon code contains invalid characters' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', 'D-15'),
            'couponCode',
            'Coupon code must contain only letters and digits.',
            ApiErrorCode::InvalidCouponCode,
        ];
    }

    public function testReportsIndependentValidationErrorsTogether(): void
    {
        $violations = $this->validator->validate(
            new PurchaseRequest(1, 'INVALID', 'unknown'),
        );

        self::assertCount(2, $violations);
        self::assertSame(ApiErrorCode::InvalidTaxNumber, $this->publicErrorCode($violations[0]));
        self::assertSame(ApiErrorCode::UnsupportedPaymentProcessor, $this->publicErrorCode($violations[1]));
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
