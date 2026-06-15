<?php

declare(strict_types=1);

namespace App\Tests\OpenMeteo;

use App\Bridge\OpenMeteo\Enum\HourlyVariable;
use App\Bridge\OpenMeteo\Exception\ApiException;
use App\Bridge\OpenMeteo\Exception\TransportException;
use App\Bridge\OpenMeteo\OpenMeteoClient;
use App\Bridge\OpenMeteo\Request\ForecastRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\Exception\TransportException as HttpTransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class OpenMeteoClientTest extends TestCase
{
    public function testForecastMapsSuccessfulResponse(): void
    {
        $body = [
            'latitude' => 48.85,
            'longitude' => 2.35,
            'elevation' => 43.0,
            'generationtime_ms' => 0.12,
            'utc_offset_seconds' => 7200,
            'timezone' => 'Europe/Paris',
            'timezone_abbreviation' => 'CEST',
            'hourly_units' => ['temperature_2m' => '°C'],
            'hourly' => [
                'time' => ['2026-06-15T00:00', '2026-06-15T01:00'],
                'temperature_2m' => [12.3, 11.8],
            ],
        ];

        $request = null;
        $http = new MockHttpClient(function (string $method, string $url, array $options) use ($body, &$request): MockResponse {
            $request = ['method' => $method, 'url' => $url, 'query' => $options['query'] ?? []];

            return new MockResponse(json_encode($body, \JSON_THROW_ON_ERROR), [
                'response_headers' => ['content-type' => 'application/json'],
            ]);
        }, 'https://api.open-meteo.com');

        $client = new OpenMeteoClient($http);
        $response = $client->forecast(
            (new ForecastRequest(48.85, 2.35))->hourly(HourlyVariable::TEMPERATURE_2M)
        );

        self::assertSame('GET', $request['method']);
        self::assertSame('https://api.open-meteo.com/v1/forecast?latitude=48.85&longitude=2.35&hourly=temperature_2m', $request['url']);

        self::assertSame(48.85, $response->latitude);
        self::assertSame('Europe/Paris', $response->timezone);
        self::assertSame(7200, $response->utcOffsetSeconds);
        self::assertNotNull($response->hourly);
        self::assertSame([12.3, 11.8], $response->hourly->get(HourlyVariable::TEMPERATURE_2M));
        self::assertSame('°C', $response->hourlyUnits['temperature_2m']);
    }

    public function testErrorBodyRaisesApiException(): void
    {
        $http = new MockHttpClient(new MockResponse(
            json_encode(['error' => true, 'reason' => 'Latitude must be in range of -90 to 90°'], \JSON_THROW_ON_ERROR),
            ['http_code' => 400, 'response_headers' => ['content-type' => 'application/json']],
        ), 'https://api.open-meteo.com');

        $client = new OpenMeteoClient($http);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Latitude must be in range');

        $client->forecast(new ForecastRequest(999.0, 2.35));
    }

    public function testTransportErrorIsWrapped(): void
    {
        $http = new MockHttpClient(static function (): MockResponse {
            throw new HttpTransportException('Connection refused');
        }, 'https://api.open-meteo.com');

        $client = new OpenMeteoClient($http);

        $this->expectException(TransportException::class);

        $client->forecast(new ForecastRequest(48.85, 2.35));
    }
}
