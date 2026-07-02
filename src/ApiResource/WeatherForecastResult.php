<?php

declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\McpTool;
use App\State\WeatherForecastProcessor;

/**
 * Result of the `weather_forecast` MCP tool. No HTTP operations: this resource exists only
 * as an MCP tool served at /mcp.
 */
#[ApiResource(
    operations: [],
    mcp: [
        'weather_forecast' => new McpTool(
            name: 'weather_forecast',
            description: 'Weather forecast for today and tomorrow at a given latitude/longitude '
                .'(France, Belgium, Luxembourg — Météo-France AROME model). Each day has 5 time '
                .'slots (Europe/Paris): night (0h), morning (9h), noon (12h), afternoon (15h), '
                .'evening (19h). Temperatures in °C, wind speeds and gusts in knots, wind '
                .'direction in degrees (bearing the wind blows from).',
            input: WeatherForecastQuery::class,
            processor: WeatherForecastProcessor::class,
            validate: true,
        ),
    ],
)]
final class WeatherForecastResult
{
    public function __construct(
        /** Latitude rounded to 2 decimals (forecast grid key). */
        public float $latitude,
        /** Longitude rounded to 2 decimals (forecast grid key). */
        public float $longitude,
        /** @var list<WeatherForecastDay> Today first, then tomorrow. */
        public array $days = [],
    ) {
    }
}
