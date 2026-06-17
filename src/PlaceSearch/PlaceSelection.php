<?php

declare(strict_types=1);

namespace App\PlaceSearch;

/**
 * Shared format for homepage autocomplete values.
 */
final class PlaceSelection
{
    public const string SPOT_PREFIX = 'spot:';
    public const string VALUE_PATTERN = '/^(?:spot:[a-z0-9][a-z0-9-]*|-?\d+(?:\.\d+)?,-?\d+(?:\.\d+)?)$/';

    public static function forSpot(string $slug): string
    {
        return self::SPOT_PREFIX.$slug;
    }

    public static function isSpot(string $value): bool
    {
        return str_starts_with($value, self::SPOT_PREFIX);
    }

    public static function spotSlug(string $value): string
    {
        return substr($value, \strlen(self::SPOT_PREFIX));
    }

    /**
     * @return array{latitude: float, longitude: float}
     */
    public static function coordinates(string $value): array
    {
        $parts = explode(',', $value, 2);

        return [
            'latitude' => (float) $parts[0],
            'longitude' => (float) ($parts[1] ?? 0),
        ];
    }
}
