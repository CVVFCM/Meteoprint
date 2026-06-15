<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Response;

/**
 * Typed view over a single-location GET /v1/forecast response.
 */
final class ForecastResponse
{
    /**
     * @param array<string, string> $hourlyUnits
     * @param array<string, string> $dailyUnits
     * @param array<string, string> $currentUnits
     * @param array<string, string> $minutely15Units
     */
    public function __construct(
        public readonly float $latitude,
        public readonly float $longitude,
        public readonly ?float $elevation,
        public readonly ?float $generationTimeMs,
        public readonly ?int $utcOffsetSeconds,
        public readonly ?string $timezone,
        public readonly ?string $timezoneAbbreviation,
        public readonly ?VariableBlock $hourly = null,
        public readonly array $hourlyUnits = [],
        public readonly ?VariableBlock $daily = null,
        public readonly array $dailyUnits = [],
        public readonly ?VariableBlock $current = null,
        public readonly array $currentUnits = [],
        public readonly ?VariableBlock $minutely15 = null,
        public readonly array $minutely15Units = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            latitude: (float) ($data['latitude'] ?? 0.0),
            longitude: (float) ($data['longitude'] ?? 0.0),
            elevation: isset($data['elevation']) ? (float) $data['elevation'] : null,
            generationTimeMs: isset($data['generationtime_ms']) ? (float) $data['generationtime_ms'] : null,
            utcOffsetSeconds: isset($data['utc_offset_seconds']) ? (int) $data['utc_offset_seconds'] : null,
            timezone: $data['timezone'] ?? null,
            timezoneAbbreviation: $data['timezone_abbreviation'] ?? null,
            hourly: isset($data['hourly']) ? VariableBlock::fromArray($data['hourly']) : null,
            hourlyUnits: $data['hourly_units'] ?? [],
            daily: isset($data['daily']) ? VariableBlock::fromArray($data['daily']) : null,
            dailyUnits: $data['daily_units'] ?? [],
            current: isset($data['current']) ? VariableBlock::fromArray($data['current']) : null,
            currentUnits: $data['current_units'] ?? [],
            minutely15: isset($data['minutely_15']) ? VariableBlock::fromArray($data['minutely_15']) : null,
            minutely15Units: $data['minutely_15_units'] ?? [],
        );
    }
}
