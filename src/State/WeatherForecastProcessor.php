<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\WeatherForecastDay;
use App\ApiResource\WeatherForecastQuery;
use App\ApiResource\WeatherForecastResult;
use App\ApiResource\WeatherForecastSlot;
use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Bridge\OpenMeteo\MessageHandler\FetchForecastHandler;
use App\Repository\ForecastRepository;
use App\ValueObject\Geo;
use App\Weather\WeatherCode;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Backs the `weather_forecast` MCP tool: today + tomorrow for a coordinate pair.
 *
 * Reuses the exact pipeline of the web pages: same repository cache, and — on a miss or a
 * stale entry — the same FetchForecastHandler, invoked directly (synchronously) instead of
 * through the async messenger transport, because an MCP client waits for the answer. The
 * side effects (persisted forecast, Mercure publish) are shared with and harmless to the
 * web flow: they warm the cache for everyone.
 *
 * @implements ProcessorInterface<WeatherForecastQuery, WeatherForecastResult>
 */
final readonly class WeatherForecastProcessor implements ProcessorInterface
{
    /** Same grid precision as ForecastPageRenderer / Mercure topics / the DB unique key. */
    private const int PRECISION = 2;

    public function __construct(
        private ForecastRepository $repository,
        private FetchForecastHandler $fetchForecast,
        private ClockInterface $clock,
        private TranslatorInterface $translator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): WeatherForecastResult
    {
        // validate: true ran the NotNull constraints before we get here.
        \assert(null !== $data->latitude && null !== $data->longitude);

        $position = new Geo(
            round($data->latitude, self::PRECISION),
            round($data->longitude, self::PRECISION),
        );

        $now = $this->clock->now();
        $today = $now->setTime(0, 0);

        $days = [];
        foreach ([$today, $today->modify('+1 day')] as $day) {
            $forecast = $this->repository->findOneForDay($position, $day);

            if (null === $forecast || $forecast->isStale($now)) {
                ($this->fetchForecast)(new FetchForecast($position, $day));
                $forecast = $this->repository->findOneForDay($position, $day);
            }

            if (null === $forecast) {
                throw new \RuntimeException(\sprintf('No forecast available for %s.', $day->format('Y-m-d')));
            }

            $slots = [];
            foreach ($forecast->slots as $slot) {
                $slots[] = new WeatherForecastSlot(
                    hour: $slot->hour,
                    weatherCode: $slot->weatherCode,
                    condition: $this->translator->trans(WeatherCode::tryFromCode($slot->weatherCode)->translationKey(), locale: 'fr'),
                    temperature: round($slot->temperature, 1),
                    windSpeed: round($slot->windSpeed, 1),
                    windGust: round($slot->windGust, 1),
                    windDirection: $slot->windDirection,
                );
            }

            $days[] = new WeatherForecastDay($day->format('Y-m-d'), $slots);
        }

        return new WeatherForecastResult($position->latitude, $position->longitude, $days);
    }
}
