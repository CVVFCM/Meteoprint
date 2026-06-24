<?php

declare(strict_types=1);

namespace App\Geocoding;

use App\ValueObject\Geo;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\ProviderAggregator;
use Geocoder\Query\ReverseQuery;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Resolves a coordinate to a human-readable address via Nominatim.
 *
 * Results are cached for 30 days: a coordinate→address mapping is stable, and
 * Nominatim's usage policy requires caching and a low request rate.
 */
final readonly class ReverseGeocoder
{
    public function __construct(
        private ProviderAggregator $geocoder,
        private CacheInterface $cache,
    ) {
    }

    public function address(Geo $position, string $locale): ?string
    {
        $key = \sprintf('reverse_geo.%s.%.2f.%.2f', $locale, $position->latitude, $position->longitude);

        $label = $this->cache->get($key, function (ItemInterface $item) use ($position, $locale): string {
            $item->expiresAfter(new \DateInterval('P30D'));

            try {
                $locations = $this->geocoder->reverseQuery(
                    ReverseQuery::fromCoordinates($position->latitude, $position->longitude)->withLocale($locale),
                );
            } catch (GeocoderException) {
                // Cache the miss to avoid hammering Nominatim on every page view.
                return '';
            }

            return $locations->isEmpty() ? '' : LocationLabel::format($locations->first());
        });

        return '' === $label ? null : $label;
    }
}
