<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\NotFoundException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Exception\UnexpectedValueException as SerializerException;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders uncaught exceptions as RFC 7807 problem+json responses, so the API
 * speaks a single, machine-readable error format.
 *
 * Only the cases we deliberately map are handled; anything else falls through
 * to Symfony's default handler (keeping the dev stack trace for real 500s).
 */
#[AsEventListener(event: 'kernel.exception')]
final class ProblemDetailsListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();
        $validationFailure = $this->validationFailure($throwable);

        $response = match (true) {
            null !== $validationFailure => $this->validationResponse($validationFailure),
            $throwable instanceof NotFoundException => $this->problemResponse(Response::HTTP_NOT_FOUND, $throwable->getMessage()),
            $throwable instanceof SerializerException => $this->problemResponse(Response::HTTP_BAD_REQUEST, 'The request body is malformed.'),
            $throwable instanceof HttpExceptionInterface => $this->problemResponse($throwable->getStatusCode(), $throwable->getMessage()),
            default => null,
        };

        if (null !== $response) {
            $event->setResponse($response);
        }
    }

    /**
     * Extracts the validation failure whether it was thrown directly (manual
     * validate()) or wrapped by an argument resolver such as #[MapQueryString],
     * which nests it as the previous exception.
     */
    private function validationFailure(\Throwable $throwable): ?ValidationFailedException
    {
        if ($throwable instanceof ValidationFailedException) {
            return $throwable;
        }

        $previous = $throwable->getPrevious();

        return $previous instanceof ValidationFailedException ? $previous : null;
    }

    private function validationResponse(ValidationFailedException $exception): JsonResponse
    {
        $violations = [];
        foreach ($exception->getViolations() as $violation) {
            $violations[] = [
                'propertyPath' => $violation->getPropertyPath(),
                'code' => $violation->getCode(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $this->problemResponse(
            Response::HTTP_UNPROCESSABLE_ENTITY,
            'The request payload failed validation.',
            ['violations' => $violations],
        );
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private function problemResponse(int $status, string $detail, array $extensions = []): JsonResponse
    {
        return new JsonResponse(
            [
                'type' => 'about:blank',
                'title' => Response::$statusTexts[$status] ?? 'Error',
                'status' => $status,
                'detail' => $detail,
                ...$extensions,
            ],
            $status,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
