<?php

declare(strict_types=1);

namespace App\ApiResource;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Input of the `weather_forecast` MCP tool: a bare coordinate pair. Spots (saved places)
 * are a human-facing concept and are deliberately not exposed to agents.
 */
final class WeatherForecastQuery
{
    #[Assert\NotNull]
    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\NotNull]
    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;
}
