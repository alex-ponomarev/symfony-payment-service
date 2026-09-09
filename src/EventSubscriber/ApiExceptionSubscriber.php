<?php

namespace App\EventSubscriber;

use App\Exception\ApiErrorCode;
use App\Exception\ClientVisibleExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Exception\NotEncodableValueException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;
use Throwable;

final class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        $event->setResponse($this->createErrorResponse($this->resolveErrors($exception)));
    }

    /** @return list<array{code: string, message: string}> */
    private function resolveErrors(Throwable $exception): array
    {
        if ($exception instanceof ClientVisibleExceptionInterface) {
            return [$this->error($exception->errorCode(), $exception->publicMessage())];
        }

        if ($exception instanceof HttpExceptionInterface) {
            if (($validationException = $this->findPrevious(
                $exception,
                ValidationFailedException::class,
            )) instanceof ValidationFailedException) {
                return $this->validationErrors($validationException);
            }

            if ($this->findPrevious($exception, NotEncodableValueException::class) !== null) {
                return [$this->error(
                    ApiErrorCode::InvalidJson,
                    'Request body contains invalid JSON.',
                )];
            }

            if ($exception->getStatusCode() === HttpFoundation\Response::HTTP_UNSUPPORTED_MEDIA_TYPE) {
                return [$this->error(
                    ApiErrorCode::UnsupportedContentType,
                    'Content-Type must be application/json.',
                )];
            }

            return [$this->error(
                ApiErrorCode::InvalidRequest,
                'Request payload is invalid.',
            )];
        }

        $this->logger->error('Unexpected error while processing the payment API request.', [
            'exception' => $exception,
        ]);

        return [$this->error(
            ApiErrorCode::InternalError,
            'An internal error occurred.',
        )];
    }

    /**
     * @return list<array{code: string, message: string}>
     */
    private function validationErrors(ValidationFailedException $exception): array
    {
        $errors = [];

        foreach ($exception->getViolations() as $violation) {
            $error = $this->validationError($violation);
            $errors[$error['code'] . "\0" . $error['message']] = $error;
        }

        return array_values($errors);
    }

    /** @return array{code: string, message: string} */
    private function validationError(ConstraintViolationInterface $violation): array
    {
        $errorCode = $this->violationErrorCode($violation);
        $constraint = $violation->getConstraint();

        if ($constraint !== null || ApiErrorCode::tryFrom((string) $violation->getCode()) !== null) {
            return $this->error($errorCode, (string) $violation->getMessage());
        }

        return $this->error(
            $errorCode,
            $this->denormalizationMessage($errorCode),
        );
    }

    private function denormalizationMessage(ApiErrorCode $errorCode): string
    {
        return match ($errorCode) {
            ApiErrorCode::InvalidProduct => 'Product identifier is required and must be an integer.',
            ApiErrorCode::InvalidTaxNumber => 'Tax number is required and must be a string.',
            ApiErrorCode::InvalidCouponCode => 'Coupon code must be a string when provided.',
            ApiErrorCode::InvalidPaymentProcessor => 'Payment processor is required and must be a string.',
            default => 'Request payload is invalid.',
        };
    }

    private function violationErrorCode(ConstraintViolationInterface $violation): ApiErrorCode
    {
        $violationCode = $violation->getCode();

        if ($violationCode !== null && ($errorCode = ApiErrorCode::tryFrom($violationCode)) !== null) {
            return $errorCode;
        }

        $payload = $violation->getConstraint()?->payload;
        $payloadCode = is_array($payload) ? ($payload['error_code'] ?? null) : null;

        if ($payloadCode instanceof ApiErrorCode) {
            return $payloadCode;
        }

        if (is_string($payloadCode) && ($errorCode = ApiErrorCode::tryFrom($payloadCode)) !== null) {
            return $errorCode;
        }

        return match ($violation->getPropertyPath()) {
            'product' => ApiErrorCode::InvalidProduct,
            'taxNumber' => ApiErrorCode::InvalidTaxNumber,
            'couponCode' => ApiErrorCode::InvalidCouponCode,
            'paymentProcessor' => ApiErrorCode::InvalidPaymentProcessor,
            default => ApiErrorCode::InvalidRequest,
        };
    }

    /**
     * @param class-string<Throwable> $class
     */
    private function findPrevious(Throwable $exception, string $class): ?Throwable
    {
        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            if ($current instanceof $class) {
                return $current;
            }
        }

        return null;
    }

    /** @return array{code: string, message: string} */
    private function error(ApiErrorCode $code, string $message): array
    {
        return [
            'code' => $code->value,
            'message' => $message,
        ];
    }

    /** @param list<array{code: string, message: string}> $errors */
    private function createErrorResponse(array $errors): HttpFoundation\JsonResponse
    {
        return new HttpFoundation\JsonResponse([
            'status' => 'error',
            'data' => $errors,
        ], HttpFoundation\Response::HTTP_BAD_REQUEST);
    }
}
