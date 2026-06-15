<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Exception;

/**
 * Thrown when the request never produced a usable response (network error, timeout, ...).
 */
final class TransportException extends \RuntimeException implements OpenMeteoExceptionInterface
{
}
