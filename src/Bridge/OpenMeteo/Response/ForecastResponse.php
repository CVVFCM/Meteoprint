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
            latitude: self::toFloat($data['latitude'] ?? null) ?? 0.0,
            longitude: self::toFloat($data['longitude'] ?? null) ?? 0.0,
            elevation: self::toFloat($data['elevation'] ?? null),
            generationTimeMs: self::toFloat($data['generationtime_ms'] ?? null),
            utcOffsetSeconds: self::toInt($data['utc_offset_seconds'] ?? null),
            timezone: self::toString($data['timezone'] ?? null),
            timezoneAbbreviation: self::toString($data['timezone_abbreviation'] ?? null),
            hourly: self::toBlock($data['hourly'] ?? null),
            hourlyUnits: self::toUnits($data['hourly_units'] ?? null),
            daily: self::toBlock($data['daily'] ?? null),
            dailyUnits: self::toUnits($data['daily_units'] ?? null),
            current: self::toBlock($data['current'] ?? null),
            currentUnits: self::toUnits($data['current_units'] ?? null),
            minutely15: self::toBlock($data['minutely_15'] ?? null),
            minutely15Units: self::toUnits($data['minutely_15_units'] ?? null),
        );
    }

    private static function toFloat(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private static function toInt(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }

    private static function toString(mixed $value): ?string
    {
        return \is_string($value) ? $value : null;
    }

    private static function toBlock(mixed $value): ?VariableBlock
    {
        return \is_array($value) ? VariableBlock::fromArray($value) : null;
    }

    /**
     * @return array<string, string>
     */
    private static function toUnits(mixed $value): array
    {
        if (!\is_array($value)) {
            return [];
        }

        $units = [];
        foreach ($value as $name => $unit) {
            if (\is_string($name) && \is_string($unit)) {
                $units[$name] = $unit;
            }
        }

        return $units;
    }
}
