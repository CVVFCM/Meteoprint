<?php

declare(strict_types=1);

namespace App\Controller;

use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\Location;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Geocoding endpoint feeding the homepage UX Autocomplete.
 *
 * Returns the JSON shape Symfony UX Autocomplete expects: `{ "results": [ { value, text } ] }`,
 * where `value` is the `"latitude,longitude"` consumed by {@see HomepageController}.
 */
final readonly class GeocodeController
{
    private const int MIN_QUERY_LENGTH = 2;
    private const int MAX_RESULTS = 8;

    public function __construct(
        private ProviderAggregator $geocoder,
    ) {
    }

    #[Route('/geocode', name: 'geocode_search', methods: [Request::METHOD_GET])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('query'));

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return new JsonResponse(['results' => []]);
        }

        try {
            $locations = $this->geocoder->geocodeQuery(
                GeocodeQuery::create($query)
                    ->withLocale($request->getLocale())
                    ->withLimit(self::MAX_RESULTS),
            );
        } catch (GeocoderException) {
            // Never break the autocomplete UI on a provider/network failure.
            return new JsonResponse(['results' => []]);
        }

        /** @var list<array{value: string, text: string}> $results */
        $results = [];
        foreach ($locations as $location) {
            $coordinates = $location->getCoordinates();
            if (null === $coordinates) {
                continue;
            }

            $results[] = [
                'value' => \sprintf('%.2f,%.2f', $coordinates->getLatitude(), $coordinates->getLongitude()),
                'text' => self::label($location),
            ];
        }

        return new JsonResponse(['results' => $results]);
    }

    private static function label(Location $location): string
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
