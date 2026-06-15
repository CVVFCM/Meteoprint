<?php

declare(strict_types=1);

namespace App\Tests\MessageHandler;

use App\Bridge\OpenMeteo\OpenMeteoClient;
use App\Entity\Forecast;
use App\Message\FetchForecast;
use App\MessageHandler\FetchForecastHandler;
use App\Repository\ForecastRepository;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

final class FetchForecastHandlerTest extends KernelTestCase
{
    public function testFetchesPersistsAndPublishes(): void
    {
        self::bootKernel();
        $container = static::getContainer();

        $em = $container->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $position = new Geo(48.85, 2.35);
        $day = new \DateTimeImmutable('2026-06-17');

        $client = new OpenMeteoClient(new MockHttpClient(
            new MockResponse(json_encode($this->forecastBody(), \JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]),
            'https://api.open-meteo.com',
        ));

        $published = null;
        $hub = $this->createMock(HubInterface::class);
        $hub->expects(self::once())
            ->method('publish')
            ->willReturnCallback(function (Update $update) use (&$published): string {
                $published = $update;

                return 'id';
            });

        $handler = new FetchForecastHandler(
            $client,
            $container->get(ForecastRepository::class),
            $hub,
            $container->get(Environment::class),
            new MockClock(new \DateTimeImmutable('2026-06-17 10:00:00')),
        );

        $handler(new FetchForecast($position, $day));

        $stored = $container->get(ForecastRepository::class)->findOneForDay($position, $day);
        self::assertNotNull($stored, 'Forecast row was persisted and is retrievable by position+day');
        self::assertSame([9, 12, 15, 19, 0], array_map(static fn (ForecastSlot $s): int => $s->hour, $stored->slots));

        $morning = $stored->slots[0] ?? null;
        self::assertNotNull($morning);
        self::assertSame(9, $morning->weatherCode);
        self::assertSame(10.9, $morning->temperature);
        self::assertSame(14.0, $morning->windSpeed);
        self::assertSame(90, $morning->windDirection);
        self::assertSame(23.0, $morning->windGust);

        self::assertNotNull($published);
        self::assertSame(['forecast/48.85/2.35'], $published->getTopics());
    }

    /**
     * @return array<string, mixed>
     */
    private function forecastBody(): array
    {
        $times = $codes = $temperatures = $windSpeeds = $windDirections = $windGusts = [];
        for ($hour = 0; $hour < 24; ++$hour) {
            $times[] = \sprintf('2026-06-17T%02d:00', $hour);
            $codes[] = $hour;
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
                'temperature_2m' => $temperatures,
                'wind_speed_10m' => $windSpeeds,
                'wind_direction_10m' => $windDirections,
                'wind_gusts_10m' => $windGusts,
            ],
        ];
    }
}
