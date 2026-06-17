<?php

declare(strict_types=1);

namespace App\ValueObject;

/**
 * A single forecast reading for one hour of the day.
 */
final readonly class ForecastSlot
{
    public function __construct(
        public int $hour,
        public int $weatherCode,
        public float $temperature,
        public float $windSpeed,
        public int $windDirection,
        // Default keeps forecasts stored before gusts were added hydratable.
        public float $windGust = 0.0,
        // Default keeps forecasts stored before day/night icon support hydratable.
        public bool $isDay = true,
    ) {
    }
}
