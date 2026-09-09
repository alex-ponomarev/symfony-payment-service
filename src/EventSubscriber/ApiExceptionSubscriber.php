<?php

namespace App\EventSubscriber;

use App\Exception\ClientVisibleExceptionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => "onKernelException",
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (!$exception instanceof ClientVisibleExceptionInterface) {
            return;
        }

        $event->setResponse(new HttpFoundation\JsonResponse(
            ["error" => $exception->getMessage()],
            HttpFoundation\Response::HTTP_UNPROCESSABLE_ENTITY,
        ));
    }
}
