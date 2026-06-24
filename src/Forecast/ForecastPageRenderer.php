<?php

declare(strict_types=1);

namespace App\Forecast;

use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Entity\Spot;
use App\Geocoding\ReverseGeocoder;
use App\Repository\ForecastRepository;
use App\ValueObject\Geo;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Translation\LocaleSwitcher;
use Twig\Environment;

final readonly class ForecastPageRenderer
{
    /**
     * Meteo-France seamless keeps high-resolution local detail; 2 decimals (~1.1 km) is enough
     * and keeps geocoder/topic/cache keys stable.
     */
    private const int PRECISION = 2;
    private const string REPLAY_CURSOR_EVENT_TYPE = 'forecast.cursor';

    public function __construct(
        private Environment $twig,
        private ForecastRepository $repository,
        private MessageBusInterface $bus,
        private ClockInterface $clock,
        private HubInterface $hub,
        private ReverseGeocoder $reverseGeocoder,
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public function render(Geo $rawPosition, ?Spot $spot = null): Response
    {
        $position = new Geo(
            round($rawPosition->latitude, self::PRECISION),
            round($rawPosition->longitude, self::PRECISION),
        );
        $topic = ForecastChannel::topic($position);
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        $lastEventId = $this->hub->publish(new Update(
            $topic,
            type: self::REPLAY_CURSOR_EVENT_TYPE,
        ));

        $days = [];
        foreach ([$today, $today->modify('+1 day')] as $day) {
            $forecast = $this->repository->findOneForDay($position, $day);

            if (null === $forecast || $forecast->isStale($now)) {
                $this->bus->dispatch(new FetchForecast($position, $day));
            }

            $days[] = ['day' => $day, 'forecast' => $forecast];
        }

        $address = null === $spot
            ? $this->reverseGeocoder->address($position, $this->localeSwitcher->getLocale())
            : null;

        return new Response($this->twig->render('forecast/index.html.twig', [
            'address' => $address,
            'days' => $days,
            'lastEventId' => $lastEventId,
            'position' => $position,
            'spot' => $spot,
            'topic' => $topic,
        ]));
    }
}
