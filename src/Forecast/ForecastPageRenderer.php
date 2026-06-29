<?php

declare(strict_types=1);

namespace App\Forecast;

use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Entity\Spot;
use App\Geocoding\ReverseGeocoder;
use App\Repository\ForecastRepository;
use App\ValueObject\Geo;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function render(Geo $rawPosition, ?Spot $spot = null, bool $amp = false): Response
    {
        $position = new Geo(
            round($rawPosition->latitude, self::PRECISION),
            round($rawPosition->longitude, self::PRECISION),
        );
        $topic = ForecastChannel::topic($position);
        $now = $this->clock->now();
        $today = $now->setTime(0, 0);
        $lastEventId = null;
        $dispatchedFetch = false;
        $staleAt = null;

        $days = [];
        foreach ([$today, $today->modify('+1 day')] as $day) {
            $forecast = $this->repository->findOneForDay($position, $day);

            if (null === $forecast || $forecast->isStale($now)) {
                // Only when something is actually being fetched: publish a replay
                // cursor so the client can catch the fetch-complete update via
                // Last-Event-ID. Fresh pages need no stream replay (avoids resurrecting
                // stale history). The Turbo target id is keyed in UTC in the template,
                // so it matches the worker's publish regardless of process timezone.
                if (!$dispatchedFetch) {
                    $lastEventId = $this->hub->publish(new Update(
                        $topic,
                        type: self::REPLAY_CURSOR_EVENT_TYPE,
                    ));
                    $dispatchedFetch = true;
                }

                $this->bus->dispatch(new FetchForecast($position, $day));
            } else {
                // Track the earliest staleness deadline across the displayed days.
                $deadline = $forecast->staleAt();
                if (null === $staleAt || $deadline < $staleAt) {
                    $staleAt = $deadline;
                }
            }

            $days[] = ['day' => $day, 'forecast' => $forecast];
        }

        // Live (Mercure stream + auth cookie) only while a fetch is pending; a fully
        // fresh page is static for the rest of its freshness window.
        $live = $dispatchedFetch;

        // AMP carries no custom JS, so it can't run the live stream. Serve AMP only for a
        // fully fresh (cacheable) page; while a fetch is pending, redirect to the canonical
        // page so a JS client gets the live updates. AMP is only wired for saved spots.
        if ($amp && $live && null !== $spot) {
            return new RedirectResponse(
                $this->urlGenerator->generate('forecast_spot', ['slug' => $spot->slug]),
            );
        }

        $address = null === $spot
            ? $this->reverseGeocoder->address($position, $this->localeSwitcher->getLocale())
            : null;

        // Advertise the AMP variant only when the canonical page is fresh (so the linked AMP
        // page won't itself redirect) and indexable (saved spots only).
        $amphtml = !$amp && !$live && null !== $spot
            ? $this->urlGenerator->generate('forecast_spot_amp', ['slug' => $spot->slug], UrlGeneratorInterface::ABSOLUTE_URL)
            : null;

        $template = $amp ? 'forecast/index.amp.html.twig' : 'forecast/index.html.twig';

        $response = new Response($this->twig->render($template, [
            'address' => $address,
            'amphtml' => $amphtml,
            'days' => $days,
            'lastEventId' => $lastEventId,
            'live' => $live,
            'position' => $position,
            'spot' => $spot,
            'topic' => $topic,
        ]));

        // Cache only when fully fresh (no loading state, no Set-Cookie from the stream):
        // public for the remaining freshness window, so Souin serves it until the
        // forecast goes stale (≤ 1 h after it was fetched).
        if (!$live && null !== $staleAt) {
            $maxAge = max(0, $staleAt->getTimestamp() - $now->getTimestamp());
            $response->setPublic();
            $response->setMaxAge($maxAge);
            $response->setSharedMaxAge($maxAge);
        }

        return $response;
    }
}
