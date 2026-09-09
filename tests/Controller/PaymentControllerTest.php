<?php

namespace App\Tests\Controller;

use App\Entity\Coupon;
use App\Entity\Product;
use App\Exception\ApiErrorCode;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class PaymentControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        $this->client->disableReboot();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();

        if ($connection->isTransactionActive()) {
            $connection->rollBack();
        }

        parent::tearDown();
    }

    #[DataProvider('successfulCalculationProvider')]
    public function testCalculatesPrice(
        string $productName,
        string $taxNumber,
        ?string $couponCode,
        int $expectedPrice,
    ): void {
        $payload = [
            'product' => $this->productId($productName),
            'taxNumber' => $taxNumber,
        ];

        if ($couponCode !== null) {
            $payload['couponCode'] = $couponCode;
        }

        $this->postJson('/calculate-price', $payload);

        self::assertSame(
            [
                'status' => 'ok',
                'data' => ['price' => $expectedPrice],
            ],
            $this->responseJson(Response::HTTP_OK),
        );
    }

    public static function successfulCalculationProvider(): iterable
    {
        yield 'without coupon' => ['Iphone', 'DE123456789', null, 11900];
        yield 'percentage coupon' => ['Iphone', 'GR123456789', 'D15', 10540];
        yield 'fixed coupon' => ['Iphone', 'FRAB123456789', 'F5', 11400];
        yield 'lowercase coupon' => ['Iphone', 'DE123456789', 'd15', 10115];
    }

    #[DataProvider('successfulPurchaseProvider')]
    public function testPurchasesProduct(string $paymentProcessor): void
    {
        $this->postJson('/purchase', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => $paymentProcessor,
        ]);

        self::assertSame(
            [
                'status' => 'ok',
                'data' => ['success' => true],
            ],
            $this->responseJson(Response::HTTP_OK),
        );
    }

    public static function successfulPurchaseProvider(): iterable
    {
        yield 'PayPal' => ['paypal'];
        yield 'Stripe' => ['stripe'];
    }

    public function testRejectsMalformedJson(): void
    {
        $this->client->request(
            'POST',
            '/calculate-price',
            server: [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: '{"product":',
        );

        $this->assertApiError(
            ApiErrorCode::InvalidJson,
            'Request body contains invalid JSON.',
        );
    }

    public function testRejectsUnsupportedContentTypeWithoutExposingException(): void
    {
        $this->client->request(
            'POST',
            '/calculate-price',
            server: [
                'CONTENT_TYPE' => 'text/plain',
                'HTTP_ACCEPT' => 'application/json',
            ],
            content: '{"product":1,"taxNumber":"DE123456789"}',
        );

        $this->assertApiError(
            ApiErrorCode::UnsupportedContentType,
            'Content-Type must be application/json.',
        );
    }

    public function testRejectsMissingProduct(): void
    {
        $this->postJson('/calculate-price', [
            'taxNumber' => 'DE123456789',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidProduct,
            'Product identifier is required and must be an integer.',
        );
    }

    public function testRejectsMissingTaxNumber(): void
    {
        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidTaxNumber,
            'Tax number is required and must be a string.',
        );
    }

    public function testRejectsMissingPaymentProcessor(): void
    {
        $this->postJson('/purchase', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidPaymentProcessor,
            'Payment processor is required and must be a string.',
        );
    }

    public function testRejectsWrongProductType(): void
    {
        $this->postJson('/calculate-price', [
            'product' => 'not-an-integer',
            'taxNumber' => 'DE123456789',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidProduct,
            'Product identifier is required and must be an integer.',
        );
    }

    public function testRejectsNonPositiveProduct(): void
    {
        $this->postJson('/calculate-price', [
            'product' => 0,
            'taxNumber' => 'DE123456789',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidProduct,
            'Product identifier must be greater than zero.',
        );
    }

    public function testRejectsInvalidTaxNumber(): void
    {
        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'INVALID',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidTaxNumber,
            'Invalid tax number.',
        );
    }

    public function testReturnsAllValidationErrors(): void
    {
        $this->postJson('/purchase', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'INVALID',
            'paymentProcessor' => 'unknown',
        ]);

        $this->assertApiErrors([
            [
                'code' => ApiErrorCode::InvalidTaxNumber->value,
                'message' => 'Invalid tax number.',
            ],
            [
                'code' => ApiErrorCode::UnsupportedPaymentProcessor->value,
                'message' => 'Unsupported payment processor.',
            ],
        ]);
    }

    public function testRejectsInvalidCouponCode(): void
    {
        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'couponCode' => 'D-15',
        ]);

        $this->assertApiError(
            ApiErrorCode::InvalidCouponCode,
            'Coupon code must contain only letters and digits.',
        );
    }

    public function testRejectsUnsupportedPaymentProcessor(): void
    {
        $this->postJson('/purchase', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => 'unknown',
        ]);

        $this->assertApiError(
            ApiErrorCode::UnsupportedPaymentProcessor,
            'Unsupported payment processor.',
        );
    }

    public function testRejectsUnknownProduct(): void
    {
        $this->postJson('/calculate-price', [
            'product' => 2147483647,
            'taxNumber' => 'DE123456789',
        ]);

        $this->assertApiError(
            ApiErrorCode::ProductNotFound,
            'Product with identifier "2147483647" was not found.',
        );
    }

    public function testRejectsUnknownCoupon(): void
    {
        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'couponCode' => 'UNKNOWN',
        ]);

        $this->assertApiError(
            ApiErrorCode::CouponNotFound,
            'Coupon with code "UNKNOWN" was not found.',
        );
    }

    public function testRejectsCouponThatHasNotStarted(): void
    {
        $coupon = new Coupon(
            'APIFUTURE',
            Coupon::TYPE_PERCENT,
            10,
            new DateTimeImmutable('+1 day'),
            new DateTimeImmutable('+2 days'),
        );
        $this->entityManager->persist($coupon);
        $this->entityManager->flush();

        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'couponCode' => 'APIFUTURE',
        ]);

        $this->assertApiError(
            ApiErrorCode::CouponNotActive,
            'Coupon with code "APIFUTURE" is not active.',
        );
    }

    public function testRejectsExpiredCoupon(): void
    {
        $coupon = new Coupon(
            'APIEXPIRED',
            Coupon::TYPE_FIXED,
            500,
            new DateTimeImmutable('-2 days'),
            new DateTimeImmutable('-1 day'),
        );
        $this->entityManager->persist($coupon);
        $this->entityManager->flush();

        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'couponCode' => 'APIEXPIRED',
        ]);

        $this->assertApiError(
            ApiErrorCode::CouponNotActive,
            'Coupon with code "APIEXPIRED" is not active.',
        );
    }

    public function testHidesUnexpectedException(): void
    {
        self::getContainer()->set('logger', new NullLogger());

        $coupon = new Coupon(
            'APIUNSUPPORTED',
            'unsupported',
            10,
            new DateTimeImmutable('-1 day'),
            new DateTimeImmutable('+1 day'),
        );
        $this->entityManager->persist($coupon);
        $this->entityManager->flush();

        $this->postJson('/calculate-price', [
            'product' => $this->productId('Iphone'),
            'taxNumber' => 'DE123456789',
            'couponCode' => 'APIUNSUPPORTED',
        ]);

        $this->assertApiError(
            ApiErrorCode::InternalError,
            'An internal error occurred.',
        );
    }

    public function testReturnsErrorWhenPaypalPaymentFails(): void
    {
        $product = new Product('Expensive API product', 100000);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->postJson('/purchase', [
            'product' => $product->getId(),
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => 'paypal',
        ]);

        $this->assertApiError(ApiErrorCode::PaymentFailed, 'Payment failed.');
    }

    public function testReturnsErrorWhenStripePaymentFails(): void
    {
        $this->postJson('/purchase', [
            'product' => $this->productId('Наушники'),
            'taxNumber' => 'DE123456789',
            'paymentProcessor' => 'stripe',
        ]);

        $this->assertApiError(ApiErrorCode::PaymentFailed, 'Payment failed.');
    }

    /** @param array<string, mixed> $payload */
    private function postJson(string $path, array $payload): void
    {
        $this->client->jsonRequest('POST', $path, $payload);
    }

    /** @return array<string, mixed> */
    private function responseJson(int $expectedStatus): array
    {
        $response = $this->client->getResponse();
        $content = $response->getContent();

        self::assertSame($expectedStatus, $response->getStatusCode());
        self::assertIsString($content);
        self::assertJson($content);

        return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
    }

    private function assertApiError(ApiErrorCode $errorCode, string $message): void
    {
        $this->assertApiErrors([[
            'code' => $errorCode->value,
            'message' => $message,
        ]]);
    }

    /** @param list<array{code: string, message: string}> $errors */
    private function assertApiErrors(array $errors): void
    {
        self::assertSame(
            [
                'status' => 'error',
                'data' => $errors,
            ],
            $this->responseJson(Response::HTTP_BAD_REQUEST),
        );
    }

    private function productId(string $name): int
    {
        $product = $this->entityManager
            ->getRepository(Product::class)
            ->findOneBy(['name' => $name]);

        if (!$product instanceof Product || $product->getId() === null) {
            self::fail(sprintf('Fixture product "%s" was not found.', $name));
        }

        return $product->getId();
    }
}
