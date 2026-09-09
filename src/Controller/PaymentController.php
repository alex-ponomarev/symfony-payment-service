<?php

namespace App\Controller;

use App\Dto;
use App\Exception;
use App\Service;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Attribute;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Attribute\AsController]
final class PaymentController
{
    private const string INTERNAL_ERROR_CODE = 'internal_error';

    public function __construct(
        private readonly Service\CheckoutService $checkoutService,
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route('/calculate-price', name: 'api_calculate_price', methods: ['POST'])]
    public function calculatePrice(
        #[Attribute\MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: HttpFoundation\Response::HTTP_BAD_REQUEST,
        )]
        Dto\CalculatePriceRequest $request,
    ): HttpFoundation\JsonResponse
    {
        try {
            $priceInCents = $this->checkoutService->calculatePrice($request);

            return $this->successResponse([
                'price' => $priceInCents,
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    #[Route('/purchase', name: 'api_purchase', methods: ['POST'])]
    public function purchase(
        #[Attribute\MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: HttpFoundation\Response::HTTP_BAD_REQUEST,
        )]
        Dto\PurchaseRequest $request,
    ): HttpFoundation\JsonResponse
    {
        try {
            $this->checkoutService->purchase($request);

            return $this->successResponse([
                'success' => true,
            ]);
        } catch (Throwable $exception) {
            return $this->errorResponse($exception);
        }
    }

    private function errorResponse(Throwable $exception): HttpFoundation\JsonResponse
    {
        if ($exception instanceof Exception\ClientVisibleExceptionInterface) {
            $errorCode = $exception->errorCode();
        } else {
            $this->logger->error('Unexpected error while processing the payment API request.', [
                'exception' => $exception,
            ]);
            $errorCode = self::INTERNAL_ERROR_CODE;
        }

        return new HttpFoundation\JsonResponse(
            [
                'status' => 'error',
                'data' => [
                    'code' => $errorCode,
                ],
            ],
            HttpFoundation\Response::HTTP_BAD_REQUEST,
        );
    }

    /** @param array<string, mixed> $data */
    private function successResponse(array $data): HttpFoundation\JsonResponse
    {
        return new HttpFoundation\JsonResponse([
            'status' => 'ok',
            'data' => $data,
        ]);
    }
}
