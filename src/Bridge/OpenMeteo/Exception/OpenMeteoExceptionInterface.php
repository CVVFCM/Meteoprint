<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Exception;

use App\Exception\ExceptionInterface;

/**
 * Marker interface for every exception thrown by the Open-Meteo SDK.
 */
interface OpenMeteoExceptionInterface extends ExceptionInterface
{
}
