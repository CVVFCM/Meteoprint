<?php

declare(strict_types=1);

namespace App\Controller;

use App\Forecast\ForecastPageRenderer;
use App\Repository\SpotRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Forecast page addressed by a saved spot slug.
 */
final readonly class SpotForecastController
{
    public function __construct(
        private SpotRepository $spots,
        private ForecastPageRenderer $renderer,
    ) {
    }

    #[Route(
        '/forecast/{slug}',
        name: 'forecast_spot',
        requirements: ['slug' => '[a-z0-9][a-z0-9-]*'],
        methods: [Request::METHOD_GET],
    )]
    #[Route(
        '/forecast/{slug}.amp',
        name: 'forecast_spot_amp',
        requirements: ['slug' => '[a-z0-9][a-z0-9-]*'],
        defaults: ['amp' => true],
        methods: [Request::METHOD_GET],
    )]
    public function __invoke(string $slug, bool $amp = false): Response
    {
        $spot = $this->spots->findOneBySlug($slug);
        if (null === $spot) {
            throw new NotFoundHttpException();
        }

        return $this->renderer->render($spot->position, $spot, $amp);
    }
}
