<?php

declare(strict_types=1);

namespace App\Controller;

use App\Geocoding\ReverseGeocoder;
use App\PlaceSearch\PlaceSelection;
use App\Repository\SpotRepository;
use App\ValueObject\Geo;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Resolves an autocomplete token ("spot:slug" or "lat,lng") back to its human label,
 * so the homepage field can show the place name when pre-filled (e.g. browser Back)
 * instead of the raw token.
 */
final readonly class PlaceLabelController
{
    public function __construct(
        private SpotRepository $spots,
        private ReverseGeocoder $reverseGeocoder,
    ) {
    }

    #[Route('/places/label', name: 'place_label', methods: [Request::METHOD_GET])]
    public function __invoke(Request $request): JsonResponse
    {
        $token = trim($request->query->getString('token'));

        if ('' === $token || 1 !== preg_match(PlaceSelection::VALUE_PATTERN, $token)) {
            return new JsonResponse(['value' => $token, 'text' => $token]);
        }

        $text = $this->label($token, $request->getLocale()) ?? $token;

        return new JsonResponse(['value' => $token, 'text' => $text]);
    }

    private function label(string $token, string $locale): ?string
    {
        if (PlaceSelection::isSpot($token)) {
            return $this->spots->findOneBySlug(PlaceSelection::spotSlug($token))?->name;
        }

        $coordinates = PlaceSelection::coordinates($token);

        return $this->reverseGeocoder->address(
            new Geo($coordinates['latitude'], $coordinates['longitude']),
            $locale,
        );
    }
}
