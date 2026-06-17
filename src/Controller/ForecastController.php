<?php

declare(strict_types=1);

namespace App\Controller;

use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Entity\Forecast;
use App\Forecast\ForecastChannel;
use App\Repository\ForecastRepository;
use App\ValueObject\Geo;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

/**
 * Forecast page, addressed by latitude/longitude path segments (no query string).
 *
 * Renders today + tomorrow; each day is loaded from a stored Forecast or, when missing/stale,
 * fetched asynchronously and pushed back to the page via a Mercure Turbo Stream.
 */
final readonly class ForecastController
{
    /**
     * Arome HD resolution is ~1.5 km, so 2 decimals (~1.1 km) is plenty. Shared with the
     * geocoder/topic so cache keys stay stable.
     */
    private const int PRECISION = 2;
    private const string REPLAY_CURSOR_EVENT_TYPE = 'forecast.cursor';

    public function __construct(
        private Environment $twig,
        private ForecastRepository $repository,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
        private HubInterface $hub,
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
        $position = new Geo(round($latitude, self::PRECISION), round($longitude, self::PRECISION));
        $topic = ForecastChannel::topic($position);
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);
        $lastEventId = null;
        $dispatchedFetch = false;

        $days = [];
        foreach ([$today, $today->modify('+1 day')] as $day) {
            $forecast = $this->repository->findOneForDay($position, $day);

            if (null === $forecast || $forecast->isStale($now)) {
                if (!$dispatchedFetch) {
                    $lastEventId = $this->hub->publish(new Update(
                        $topic,
                        type: self::REPLAY_CURSOR_EVENT_TYPE,
                    ));
                    $dispatchedFetch = true;
                }

                $this->bus->dispatch(new FetchForecast($position, $day));
            }

            $days[] = ['day' => $day, 'forecast' => $forecast];
        }

        return new Response($this->twig->render('forecast/index.html.twig', [
            'position' => $position,
            'topic' => $topic,
            'days' => $days,
            'lastEventId' => $lastEventId,
        ]));
    }
}
