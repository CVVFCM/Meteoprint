<?php

declare(strict_types=1);

namespace App\Controller;

use App\Forecast\ForecastPageRenderer;
use App\ValueObject\Geo;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Forecast page, addressed by latitude/longitude path segments (no query string).
 */
final readonly class ForecastController
{
    public function __construct(
        private ForecastPageRenderer $renderer,
    ) {
    }

    #[Route(
        '/forecast/{latitude}/{longitude}',
        name: 'forecast',
        requirements: [
            'latitude' => '-?\d+(?:\.\d{1,2})?',
            'longitude' => '-?\d+(?:\.\d{1,2})?',
        ],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(float $latitude, float $longitude): Response
    {
        return $this->renderer->render(new Geo($latitude, $longitude));
    }
}
