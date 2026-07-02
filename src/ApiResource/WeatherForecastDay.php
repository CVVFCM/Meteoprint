<?php

declare(strict_types=1);

namespace App\ApiResource;

/**
 * One forecast day of the `weather_forecast` MCP tool result.
 */
final class WeatherForecastDay
{
    public function __construct(
        /** Day in Y-m-d format (Europe/Paris). */
        public string $date,
        /** @var list<WeatherForecastSlot> */
        public array $slots = [],
    ) {
    }
}
