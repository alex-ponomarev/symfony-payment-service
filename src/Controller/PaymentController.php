<?php

namespace App\Controller;

use App\Dto;
use App\Service;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Attribute;
use Symfony\Component\Routing\Attribute\Route;

#[Attribute\AsController]
final class PaymentController
{
    public function __construct(
        private readonly Service\CheckoutService $checkoutService,
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
        return $this->successResponse([
            'price' => $this->checkoutService->calculatePrice($request),
        ]);
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
        $this->checkoutService->purchase($request);

        return $this->successResponse([
            'success' => true,
        ]);
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
