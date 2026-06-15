<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Forecast page, addressed by latitude/longitude path segments (no query string).
 *
 * Stub for now: rendering the actual forecast is out of scope.
 */
final readonly class ForecastController
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    #[Route(
        '/forecast/{latitude}/{longitude}',
        name: 'forecast',
        requirements: [
            'latitude' => '-?\d+(?:\.\d+)?',
            'longitude' => '-?\d+(?:\.\d+)?',
        ],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(float $latitude, float $longitude): Response
    {
        return new Response($this->twig->render('forecast/index.html.twig', [
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]));
    }
}
