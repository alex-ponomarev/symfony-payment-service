<?php

namespace App\Controller;

use App\Dto\CalculatePriceRequest;
use App\Dto\PurchaseRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class PaymentController
{
    #[Route('/calculate-price', name: 'api_calculate_price', methods: ['POST'])]
    public function calculatePrice(
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        CalculatePriceRequest $request,
    ): JsonResponse
    {
        return new JsonResponse(
            ['message' => 'Price calculation is not implemented yet.'],
            Response::HTTP_NOT_IMPLEMENTED,
        );
    }

    #[Route('/purchase', name: 'api_purchase', methods: ['POST'])]
    public function purchase(
        #[MapRequestPayload(
            acceptFormat: 'json',
            validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY,
        )]
        PurchaseRequest $request,
    ): JsonResponse
    {
        return new JsonResponse(
            ['message' => 'Purchase is not implemented yet.'],
            Response::HTTP_NOT_IMPLEMENTED,
        );
    }
}
