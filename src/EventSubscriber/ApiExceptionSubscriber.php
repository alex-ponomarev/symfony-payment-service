<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    private const INVALID_REQUEST_ERROR_CODE = 'invalid_request';

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof HttpExceptionInterface) {
            return;
        }

        $event->setResponse($this->createErrorResponse());
    }

    private function createErrorResponse(): HttpFoundation\JsonResponse
    {
        return new HttpFoundation\JsonResponse(
            [
                'status' => 'error',
                'data' => [
                    'code' => self::INVALID_REQUEST_ERROR_CODE,
                ],
            ],
            HttpFoundation\Response::HTTP_BAD_REQUEST,
        );
    }
}
