<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Exception;

/**
 * Thrown when the API responds with an error status or an {"error": true, "reason": ...} body.
 */
final class ApiException extends \RuntimeException implements OpenMeteoExceptionInterface
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
