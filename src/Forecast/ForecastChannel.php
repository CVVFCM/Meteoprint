<?php

declare(strict_types=1);

namespace App\Forecast;

use App\ValueObject\Geo;

/**
 * Single source of truth for the Mercure topic a forecast page subscribes to and the
 * handler publishes to. Coordinates are formatted at a fixed precision so subscriber and
 * publisher always agree on the exact topic string.
 */
final class ForecastChannel
{
    public static function topic(Geo $position): string
    {
        return \sprintf('forecast/%.2f/%.2f', $position->latitude, $position->longitude);
    }
}
