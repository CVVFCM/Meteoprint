<?php

declare(strict_types=1);

namespace App\Tests\State;

use ApiPlatform\Metadata\McpTool;
use App\ApiResource\WeatherForecastQuery;
use App\Bridge\OpenMeteo\MessageHandler\FetchForecastHandler;
use App\Bridge\OpenMeteo\OpenMeteoClient;
use App\Entity\Forecast;
use App\Repository\ForecastRepository;
use App\State\WeatherForecastProcessor;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

final class WeatherForecastProcessorTest extends KernelTestCase
{
    public function testMapsCachedForecastsAndRoundsCoordinatesWithoutFetching(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $clock = new MockClock(new \DateTimeImmutable('2026-06-17 10:00:00'));
        $position = new Geo(48.86, 2.35);
        $repository = $container->get(ForecastRepository::class);
        $repository->save(Forecast::create($position, new \DateTimeImmutable('2026-06-17'), $this->slots(), $clock->now()));
        $repository->save(Forecast::create($position, new \DateTimeImmutable('2026-06-18'), $this->slots(), $clock->now()));

        // Zero queued responses: any fetch attempt would throw — asserts the cache-hit path.
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::never())->method('publish');
        $processor = $this->processor($container->get(ForecastRepository::class), new MockHttpClient([]), $hub, $clock);

        $query = new WeatherForecastQuery();
        $query->latitude = 48.8566;
        $query->longitude = 2.3522;

        $result = $processor->process($query, new McpTool());

        self::assertSame(48.86, $result->latitude);
        self::assertSame(2.35, $result->longitude);
        self::assertSame(['2026-06-17', '2026-06-18'], array_map(static fn ($d): string => $d->date, $result->days));

        $firstDay = $result->days[0] ?? self::fail('first day is present');
        $slot = $firstDay->slots[0] ?? self::fail('first slot is present');
        self::assertSame(9, $slot->hour);
        self::assertSame(1, $slot->weatherCode);
        self::assertSame('Plutôt dégagé', $slot->condition);
        self::assertSame(15.0, $slot->temperature);
        self::assertSame(12.0, $slot->windSpeed);
        self::assertSame(18.0, $slot->windGust);
        self::assertSame(90, $slot->windDirection);

        self::assertSame('Pluie modérée', ($firstDay->slots[1] ?? self::fail('second slot is present'))->condition);
    }

    public function testFetchesSynchronouslyOnCacheMiss(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $clock = new MockClock(new \DateTimeImmutable('2026-06-17 10:00:00'));
        $httpClient = new MockHttpClient([
            new MockResponse(json_encode($this->forecastBody('2026-06-17'), \JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]),
            new MockResponse(json_encode($this->forecastBody('2026-06-18'), \JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]),
        ], 'https://api.open-meteo.com');

        // The fetch path publishes one Mercure update per fetched day (shared web pipeline).
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::exactly(2))->method('publish')->willReturn('id');
        $processor = $this->processor($container->get(ForecastRepository::class), $httpClient, $hub, $clock);

        $query = new WeatherForecastQuery();
        $query->latitude = 48.85;
        $query->longitude = 2.35;

        $result = $processor->process($query, new McpTool());

        self::assertCount(2, $result->days);
        $firstDay = $result->days[0];
        self::assertSame([9, 12, 15, 19, 0], array_map(static fn ($s): int => $s->hour, $firstDay->slots));
        self::assertSame('Plutôt dégagé', ($firstDay->slots[0] ?? self::fail('first slot is present'))->condition);

        $stored = $container->get(ForecastRepository::class)->findOneForDay(new Geo(48.85, 2.35), new \DateTimeImmutable('2026-06-17'));
        self::assertNotNull($stored, 'The synchronous fetch persisted the forecast for the web cache too');
    }

    private function processor(ForecastRepository $repository, MockHttpClient $httpClient, HubInterface $hub, MockClock $clock): WeatherForecastProcessor
    {
        $container = static::getContainer();

        $handler = new FetchForecastHandler(
            new OpenMeteoClient($httpClient),
            $repository,
            $hub,
            $container->get(Environment::class),
            $clock,
        );

        return new WeatherForecastProcessor(
            $repository,
            $handler,
            $clock,
            $container->get(TranslatorInterface::class),
        );
    }

    /**
     * @return list<ForecastSlot>
     */
    private function slots(): array
    {
        return [
            new ForecastSlot(hour: 9, weatherCode: 1, temperature: 15.0, windSpeed: 12.0, windDirection: 90, windGust: 18.0),
            new ForecastSlot(hour: 12, weatherCode: 63, temperature: 17.0, windSpeed: 14.0, windDirection: 120, windGust: 22.0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function forecastBody(string $date): array
    {
        $times = $codes = $isDay = $temperatures = $windSpeeds = $windDirections = $windGusts = [];
        for ($hour = 0; $hour < 24; ++$hour) {
            $times[] = \sprintf('%sT%02d:00', $date, $hour);
            $codes[] = match ($hour) {
                0 => 45,
                9 => 1,
                12 => 63,
                15 => 95,
                19 => 3,
                default => 0,
            };
            $isDay[] = $hour >= 7 && $hour < 23 ? 1 : 0;
            $temperatures[] = 10 + $hour * 0.1;
            $windSpeeds[] = 5 + $hour;
            $windDirections[] = ($hour * 10) % 360;
            $windGusts[] = 14 + $hour;
        }

        return [
            'latitude' => 48.85,
            'longitude' => 2.35,
            'hourly' => [
                'time' => $times,
                'weather_code' => $codes,
                'is_day' => $isDay,
                'temperature_2m' => $temperatures,
                'wind_speed_10m' => $windSpeeds,
                'wind_direction_10m' => $windDirections,
                'wind_gusts_10m' => $windGusts,
            ],
        ];
    }
}
