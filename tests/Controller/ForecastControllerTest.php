<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Entity\Forecast;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ForecastControllerTest extends WebTestCase
{
    public function testRendersTwoLoadingColumnsAndDispatchesPerDay(): void
    {
        $client = static::createClient();
        $hub = new TestHub('forecast-cursor-1');
        static::getContainer()->set(HubInterface::class, $hub);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $crawler = $client->request('GET', '/forecast/48.85/2.35');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('turbo-mercure-stream-source'));
        self::assertCount(2, $crawler->filter('.day'));
        self::assertCount(2, $crawler->filter('.day__loading'));
        self::assertSame(
            'https://localhost/.well-known/mercure?topic=forecast%2F48.85%2F2.35&lastEventID=forecast-cursor-1&Last-Event-ID=forecast-cursor-1',
            (string) $crawler->filter('turbo-mercure-stream-source')->attr('src'),
        );
        self::assertCount(1, $hub->updates);
        self::assertSame(['forecast/48.85/2.35'], $hub->updates[0]->getTopics());
        self::assertSame('forecast.cursor', $hub->updates[0]->getType());

        $transport = static::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        $sent = $transport->getSent();
        self::assertCount(2, $sent, 'one FetchForecast dispatched per missing day');
        foreach ($sent as $envelope) {
            self::assertInstanceOf(FetchForecast::class, $envelope->getMessage());
        }
    }

    public function testFreshForecastsRenderWithoutReplayCursorOrDispatch(): void
    {
        $client = static::createClient();
        $hub = new TestHub('forecast-cursor-1');
        static::getContainer()->set(HubInterface::class, $hub);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $position = new Geo(48.85, 2.35);
        $generatedAt = new \DateTimeImmutable('now');
        $repository = static::getContainer()->get(\App\Repository\ForecastRepository::class);

        $repository->save(Forecast::create($position, new \DateTimeImmutable('today'), $this->slots(), $generatedAt));
        $repository->save(Forecast::create($position, new \DateTimeImmutable('tomorrow'), $this->slots(), $generatedAt));

        $crawler = $client->request('GET', '/forecast/48.85/2.35');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('turbo-mercure-stream-source'));
        self::assertSame(
            'https://localhost/.well-known/mercure?topic=forecast%2F48.85%2F2.35',
            (string) $crawler->filter('turbo-mercure-stream-source')->attr('src'),
        );
        self::assertCount(0, $crawler->filter('.day__loading'));
        self::assertCount(0, $hub->updates);

        $transport = static::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(0, $transport->getSent());
    }

    public function testNonNumericCoordinatesAreRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forecast/paris/london');

        self::assertResponseStatusCodeSame(404);
    }

    public function testMoreThanTwoDecimalsIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forecast/48.853/2.35');

        self::assertResponseStatusCodeSame(404);
    }

    /**
     * @return list<ForecastSlot>
     */
    private function slots(): array
    {
        return [
            new ForecastSlot(
                hour: 9,
                weatherCode: 1,
                temperature: 15.0,
                windSpeed: 12.0,
                windDirection: 90,
                windGust: 18.0,
            ),
        ];
    }
}

final class TestHub implements HubInterface
{
    /**
     * @var list<Update>
     */
    public array $updates = [];

    public function __construct(
        private readonly string $eventId,
    ) {
    }

    public function getPublicUrl(): string
    {
        return 'https://localhost/.well-known/mercure';
    }

    public function getFactory(): ?TokenFactoryInterface
    {
        return null;
    }

    public function publish(Update $update): string
    {
        $this->updates[] = $update;

        return $this->eventId;
    }
}
