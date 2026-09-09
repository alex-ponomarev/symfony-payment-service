<?php

namespace App\Tests\Dto;

use App\Dto\PurchaseRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
    ): void {
        $violations = $this->validator->validate($request);

        self::assertCount(1, $violations);
        self::assertSame($expectedPath, $violations[0]->getPropertyPath());
        self::assertSame($expectedMessage, $violations[0]->getMessage());
    }

    public static function invalidRequestProvider(): iterable
    {
        yield 'non-positive product' => [
            new PurchaseRequest(0, 'DE123456789', 'paypal'),
            'product',
            'Product identifier must be greater than zero.',
        ];
        yield 'blank tax number' => [
            new PurchaseRequest(1, '', 'paypal'),
            'taxNumber',
            'Tax number is required.',
        ];
        yield 'invalid tax number' => [
            new PurchaseRequest(1, 'DE123', 'paypal'),
            'taxNumber',
            'Invalid tax number.',
        ];
        yield 'blank payment processor' => [
            new PurchaseRequest(1, 'DE123456789', ''),
            'paymentProcessor',
            'Payment processor is required.',
        ];
        yield 'unsupported payment processor' => [
            new PurchaseRequest(1, 'DE123456789', 'applepay'),
            'paymentProcessor',
            'Unsupported payment processor.',
        ];
        yield 'blank coupon code' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', ''),
            'couponCode',
            'Coupon code cannot be blank.',
        ];
        yield 'coupon code is too long' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', str_repeat('A', 101)),
            'couponCode',
            'Coupon code cannot exceed 100 characters.',
        ];
        yield 'coupon code contains invalid characters' => [
            new PurchaseRequest(1, 'DE123456789', 'paypal', 'D-15'),
            'couponCode',
            'Coupon code must contain only letters and digits.',
        ];
    }
}
