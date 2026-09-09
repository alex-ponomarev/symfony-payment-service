<?php

namespace App\Tests\Dto;

use App\Dto\CalculatePriceRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
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
    ): void {
        $violations = $this->validator->validate($request);

        self::assertCount(1, $violations);
        self::assertSame($expectedPath, $violations[0]->getPropertyPath());
        self::assertSame($expectedMessage, $violations[0]->getMessage());
    }

    public static function invalidRequestProvider(): iterable
    {
        yield 'non-positive product' => [
            new CalculatePriceRequest(0, 'DE123456789'),
            'product',
            'Product identifier must be greater than zero.',
        ];
        yield 'blank tax number' => [
            new CalculatePriceRequest(1, ''),
            'taxNumber',
            'Tax number is required.',
        ];
        yield 'invalid tax number' => [
            new CalculatePriceRequest(1, 'DE123'),
            'taxNumber',
            'Invalid tax number.',
        ];
        yield 'blank coupon code' => [
            new CalculatePriceRequest(1, 'DE123456789', ''),
            'couponCode',
            'Coupon code cannot be blank.',
        ];
        yield 'coupon code is too long' => [
            new CalculatePriceRequest(1, 'DE123456789', str_repeat('A', 101)),
            'couponCode',
            'Coupon code cannot exceed 100 characters.',
        ];
        yield 'coupon code contains invalid characters' => [
            new CalculatePriceRequest(1, 'DE123456789', 'D-15'),
            'couponCode',
            'Coupon code must contain only letters and digits.',
        ];
    }
}
