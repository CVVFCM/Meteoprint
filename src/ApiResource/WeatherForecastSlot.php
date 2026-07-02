<?php

declare(strict_types=1);

namespace App\ApiResource;

/**
 * One time slot of a forecast day, as returned by the `weather_forecast` MCP tool.
 */
final class WeatherForecastSlot
{
    public function __construct(
        /** Hour of day (Europe/Paris): 0 = night, 9 = morning, 12 = noon, 15 = afternoon, 19 = evening. */
        public int $hour,
        /** WMO weather code. */
        public int $weatherCode,
        /** Human-readable weather condition (French). */
        public string $condition,
        /** Air temperature in °C. */
        public float $temperature,
        /** Mean wind speed in knots. */
        public float $windSpeed,
        /** Wind gusts in knots. */
        public float $windGust,
        /** Direction the wind blows FROM, in degrees. */
        public int $windDirection,
    ) {
    }
}
