<?php

declare(strict_types=1);

namespace App\Geocoding;

use App\ValueObject\Geo;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\ProviderAggregator;
use Geocoder\Query\ReverseQuery;

/**
 * Resolves a coordinate to a human-readable address via Nominatim.
 *
 * The provider is cached at the bundle level (see config/packages/bazinga_geocoder.yaml),
 * so repeated lookups don't hit Nominatim.
 */
final readonly class ReverseGeocoder
{
    public function __construct(
        private ProviderAggregator $geocoder,
    ) {
    }

    public function address(Geo $position, string $locale): ?string
    {
        try {
            $locations = $this->geocoder->reverseQuery(
                ReverseQuery::fromCoordinates($position->latitude, $position->longitude)->withLocale($locale),
            );
        } catch (GeocoderException) {
            return null;
        }

        return $locations->isEmpty() ? null : LocationLabel::format($locations->first());
    }
}
