<?php

declare(strict_types=1);

namespace App\Controller;

use App\Geocoding\LocationLabel;
use App\PlaceSearch\PlaceSelection;
use App\Repository\SpotRepository;
use Geocoder\Exception\Exception as GeocoderException;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Geocoding endpoint feeding the homepage UX Autocomplete.
 *
 * Returns the grouped shape UX Autocomplete expects:
 * `{ "results": { "options": [ { value, text, group_by } ], "optgroups": [ { value, label } ] } }`.
 * Saved spots are listed first, then geocoded places. `value` is either a `spot:{slug}` token
 * for saved spots or a `"latitude,longitude"` pair for plain geocoded places.
 */
final readonly class GeocodeController
{
    private const int MIN_QUERY_LENGTH = 2;
    private const int MAX_RESULTS = 8;

    /**
     * AROME HD coverage area — geocoding is constrained to these countries.
     *
     * @var list<string>
     */
    private const array COUNTRIES = ['fr', 'be', 'lu'];

    public function __construct(
        private ProviderAggregator $geocoder,
        private SpotRepository $spots,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/geocode', name: 'geocode_search', methods: [Request::METHOD_GET])]
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim($request->query->getString('query'));

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return new JsonResponse(['results' => ['options' => [], 'optgroups' => []]]);
        }

        // Group keys are the (translated) labels, and optgroups use value === label, exactly like
        // Symfony UX Autocomplete's own EntityAutocomplete output — TomSelect groups options by
        // matching their `group_by` against the optgroup `value`.
        $spotsLabel = $this->translator->trans('homepage.search.group.spots');
        $placesLabel = $this->translator->trans('homepage.search.group.places');

        $spotOptions = $this->spotOptions($query, $spotsLabel);
        $placeOptions = $this->placeOptions($query, $request->getLocale(), $placesLabel);

        $optgroups = [];
        if ([] !== $spotOptions) {
            $optgroups[] = ['value' => $spotsLabel, 'label' => $spotsLabel];
        }
        if ([] !== $placeOptions) {
            $optgroups[] = ['value' => $placesLabel, 'label' => $placesLabel];
        }

        return new JsonResponse([
            'results' => [
                'options' => array_merge($spotOptions, $placeOptions),
                'optgroups' => $optgroups,
            ],
        ]);
    }

    /**
     * @return list<array{value: string, text: string, group_by: list<string>}>
     */
    private function spotOptions(string $query, string $group): array
    {
        $options = [];
        foreach ($this->spots->search($query) as $spot) {
            $options[] = [
                'value' => PlaceSelection::forSpot($spot->slug),
                'text' => $spot->name,
                'group_by' => [$group],
            ];
        }

        return $options;
    }

    /**
     * @return list<array{value: string, text: string, group_by: list<string>}>
     */
    private function placeOptions(string $query, string $locale, string $group): array
    {
        try {
            $locations = $this->geocoder->geocodeQuery(
                GeocodeQuery::create($query)
                    ->withLocale($locale)
                    ->withLimit(self::MAX_RESULTS)
                    ->withData('countrycodes', self::COUNTRIES),
            );
        } catch (GeocoderException) {
            // Never break the autocomplete UI on a provider/network failure.
            return [];
        }

        $options = [];
        foreach ($locations as $location) {
            $coordinates = $location->getCoordinates();
            if (null === $coordinates) {
                continue;
            }

            $options[] = [
                'value' => \sprintf('%.2f,%.2f', $coordinates->getLatitude(), $coordinates->getLongitude()),
                'text' => LocationLabel::format($location),
                'group_by' => [$group],
            ];
        }

        return $options;
    }
}
