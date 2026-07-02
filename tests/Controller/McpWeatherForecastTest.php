<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Forecast;
use App\Repository\ForecastRepository;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Functional coverage of the `weather_forecast` MCP tool through the real /mcp
 * endpoint (JSON-RPC over streamable HTTP).
 */
final class McpWeatherForecastTest extends WebTestCase
{
    public function testToolIsDiscoverableAndReturnsForecast(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        // Seed fresh forecasts so the call is a pure cache hit (no Open-Meteo traffic).
        $position = new Geo(48.86, 2.35);
        $generatedAt = new \DateTimeImmutable('now');
        $repository = static::getContainer()->get(ForecastRepository::class);
        $repository->save(Forecast::create($position, new \DateTimeImmutable('today'), $this->slots(), $generatedAt));
        $repository->save(Forecast::create($position, new \DateTimeImmutable('tomorrow'), $this->slots(), $generatedAt));

        $sessionId = $this->initializeSession($client);

        // tools/list: the tool is discoverable with a bounded lat/lng input schema.
        $tools = $this->rpc($client, $sessionId, ['jsonrpc' => '2.0', 'id' => 2, 'method' => 'tools/list']);
        /** @var array{tools?: list<array{name: string, inputSchema: array{required: list<string>, properties: array<string, array{minimum?: int, maximum?: int}>}}>} $listResult */
        $listResult = $tools['result'] ?? [];
        $toolList = $listResult['tools'] ?? [];
        $tool = null;
        foreach ($toolList as $candidate) {
            if ('weather_forecast' === $candidate['name']) {
                $tool = $candidate;
            }
        }
        self::assertNotNull($tool, 'weather_forecast is listed');
        self::assertSame(['latitude', 'longitude'], $tool['inputSchema']['required']);
        self::assertSame(-90, $tool['inputSchema']['properties']['latitude']['minimum'] ?? null);
        self::assertSame(90, $tool['inputSchema']['properties']['latitude']['maximum'] ?? null);

        // tools/call: plain-JSON structured content, rounded coordinates, 2 days.
        $call = $this->rpc($client, $sessionId, [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => ['name' => 'weather_forecast', 'arguments' => ['latitude' => 48.8566, 'longitude' => 2.3522]],
        ]);

        /** @var array{isError: bool, structuredContent: array{latitude: float, longitude: float, days: list<array{date: string, slots: list<array{hour: int, weatherCode: int, condition: string, temperature: float|int, windSpeed: float|int, windGust: float|int, windDirection: int}>}>}} $result */
        $result = $call['result'] ?? [];
        self::assertFalse($result['isError']);
        $content = $result['structuredContent'];
        self::assertArrayNotHasKey('@context', $content, 'tool payload is plain JSON, not JSON-LD');
        self::assertSame(48.86, $content['latitude']);
        self::assertSame(2.35, $content['longitude']);
        self::assertCount(2, $content['days']);

        $firstDay = $content['days'][0];
        self::assertSame((new \DateTimeImmutable('today'))->format('Y-m-d'), $firstDay['date']);

        $slot = $firstDay['slots'][0] ?? self::fail('first slot is present');
        self::assertSame(9, $slot['hour']);
        self::assertSame(1, $slot['weatherCode']);
        self::assertSame('Plutôt dégagé', $slot['condition']);
        // JSON numbers lose the float/int distinction in transit — compare by value.
        self::assertEqualsWithDelta(15.0, $slot['temperature'], 0.001);
        self::assertEqualsWithDelta(12.0, $slot['windSpeed'], 0.001);
        self::assertEqualsWithDelta(18.0, $slot['windGust'], 0.001);
        self::assertSame(90, $slot['windDirection']);
    }

    public function testOutOfRangeLatitudeIsRejectedAsToolError(): void
    {
        $client = static::createClient();
        $sessionId = $this->initializeSession($client);

        $call = $this->rpc($client, $sessionId, [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => ['name' => 'weather_forecast', 'arguments' => ['latitude' => 200, 'longitude' => 4.6]],
        ]);

        self::assertArrayHasKey('error', $call, 'validation failure surfaces as a JSON-RPC error, not a 500');
        /** @var array{code: int, message: string} $error */
        $error = $call['error'];
        self::assertStringContainsString('latitude', $error['message']);
    }

    private function initializeSession(KernelBrowser $client): string
    {
        $response = $this->rpc($client, null, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => '2025-06-18',
                'capabilities' => new \stdClass(),
                'clientInfo' => ['name' => 'phpunit', 'version' => '1.0'],
            ],
        ]);

        self::assertArrayHasKey('result', $response);
        /** @var array{serverInfo: array{name: string}} $initResult */
        $initResult = $response['result'];
        self::assertSame('meteoprint', $initResult['serverInfo']['name']);

        $sessionId = $client->getResponse()->headers->get('mcp-session-id');
        self::assertNotNull($sessionId, 'initialize returns a session id header');

        // Complete the handshake before using the session.
        $this->rpc($client, $sessionId, ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'], expectBody: false);

        return $sessionId;
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function rpc(KernelBrowser $client, ?string $sessionId, array $payload, bool $expectBody = true): array
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json, text/event-stream',
        ];
        if (null !== $sessionId) {
            $server['HTTP_MCP_SESSION_ID'] = $sessionId;
        }

        $client->request('POST', '/mcp', server: $server, content: json_encode($payload, \JSON_THROW_ON_ERROR));

        if (!$expectBody) {
            return [];
        }

        $body = (string) $client->getResponse()->getContent();
        self::assertJson($body);

        /** @var array<string, mixed> $decoded */
        $decoded = json_decode($body, true, 512, \JSON_THROW_ON_ERROR);

        return $decoded;
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
}
