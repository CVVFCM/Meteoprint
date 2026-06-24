<?php

declare(strict_types=1);

namespace App\Geocoding;

use Geocoder\Location;

/**
 * Formats a geocoder Location into a human-readable label
 * (locality, first admin level, country; coordinate fallback).
 */
final class LocationLabel
{
    public static function format(Location $location): string
    {
        $adminLevels = $location->getAdminLevels();

        $parts = array_filter([
            $location->getLocality(),
            $adminLevels->count() > 0 ? $adminLevels->first()->getName() : null,
            $location->getCountry()?->getName(),
        ], static fn (?string $part): bool => null !== $part && '' !== $part);

        if ([] === $parts) {
            $coordinates = $location->getCoordinates();

            return null !== $coordinates
                ? \sprintf('%.4f, %.4f', $coordinates->getLatitude(), $coordinates->getLongitude())
                : '';
        }

        return implode(', ', $parts);
    }
}
