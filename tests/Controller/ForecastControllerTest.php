<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Entity\Forecast;
use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\ForecastSlot;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Jwt\LcobucciFactory;
use Symfony\Component\Mercure\Jwt\TokenFactoryInterface;
use Symfony\Component\Mercure\Update;
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
        self::assertStringContainsString('Meteoprint', trim($crawler->filter('title')->text()));
        self::assertNotSame('', (string) $crawler->filter('meta[name="description"]')->attr('content'));
        self::assertSame('noindex,follow', (string) $crawler->filter('meta[name="robots"]')->attr('content'));
        self::assertCount(0, $crawler->filter('link[rel="canonical"]'));
        self::assertCount(0, $crawler->filter('meta[property="og:url"]'));
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
        $weatherLabels = array_map(
            $this->normalizeText(...),
            $crawler->filter('.slot__weather-label')->each(static fn ($node): string => $node->text()),
        );
        self::assertContains('Plutot degage', $weatherLabels);
        self::assertContains('Pluie moderee', $weatherLabels);
        self::assertCount(0, $hub->updates);

        $transport = static::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(0, $transport->getSent());
    }

    public function testSlugRouteRendersSpotNameAndReusesRoundedForecastCoordinates(): void
    {
        $client = static::createClient();
        $hub = new TestHub('forecast-cursor-1');
        static::getContainer()->set(HubInterface::class, $hub);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();
        $em->createQuery('DELETE FROM '.Spot::class)->execute();

        $repository = static::getContainer()->get(\App\Repository\ForecastRepository::class);
        $position = new Geo(48.86, 2.35);
        $generatedAt = new \DateTimeImmutable('now');
        $repository->save(Forecast::create($position, new \DateTimeImmutable('today'), $this->slots(), $generatedAt));
        $repository->save(Forecast::create($position, new \DateTimeImmutable('tomorrow'), $this->slots(), $generatedAt));

        $em->persist(Spot::create('Paris Voile', 'paris-voile', new Geo(48.8566, 2.3522), SpotType::FFV_CLUB));
        $em->flush();

        $crawler = $client->request('GET', '/forecast/paris-voile');

        self::assertResponseIsSuccessful();
        self::assertSame('Paris Voile', trim($crawler->filter('.forecast__spot')->text()));
        self::assertSame(
            'https://localhost/.well-known/mercure?topic=forecast%2F48.86%2F2.35',
            (string) $crawler->filter('turbo-mercure-stream-source')->attr('src'),
        );
        self::assertStringContainsString('Paris Voile', trim($crawler->filter('title')->text()));
        self::assertStringContainsString('Meteoprint', trim($crawler->filter('title')->text()));
        self::assertStringContainsString('Paris Voile', (string) $crawler->filter('meta[name="description"]')->attr('content'));
        self::assertSame('index,follow', (string) $crawler->filter('meta[name="robots"]')->attr('content'));
        self::assertSame('http://localhost/forecast/paris-voile', (string) $crawler->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame('http://localhost/forecast/paris-voile', (string) $crawler->filter('meta[property="og:url"]')->attr('content'));
        self::assertStringContainsString('Paris Voile', (string) $crawler->filter('meta[property="og:title"]')->attr('content'));
        self::assertStringContainsString('Meteoprint', (string) $crawler->filter('meta[property="og:title"]')->attr('content'));
        self::assertCount(0, $crawler->filter('.day__loading'));
        $weatherLabels = array_map(
            $this->normalizeText(...),
            $crawler->filter('.slot__weather-label')->each(static fn ($node): string => $node->text()),
        );
        self::assertContains('Plutot degage', $weatherLabels);
        self::assertContains('Pluie moderee', $weatherLabels);
        self::assertCount(0, $hub->updates);

        $transport = static::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        self::assertCount(0, $transport->getSent());
    }

    public function testUnknownSpotSlugIsRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forecast/unknown-spot');

        self::assertResponseStatusCodeSame(404);
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
            new ForecastSlot(
                hour: 12,
                weatherCode: 63,
                temperature: 17.0,
                windSpeed: 14.0,
                windDirection: 120,
                windGust: 22.0,
            ),
        ];
    }

    private function normalizeText(string $text): string
    {
        return str_replace(
            ['é', 'è', 'ê', 'à', 'ù', 'ô', 'î', 'ï', "\u{00A0}"],
            ['e', 'e', 'e', 'a', 'u', 'o', 'i', 'i', ' '],
            trim($text),
        );
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

    public function getFactory(): TokenFactoryInterface
    {
        // The forecast page mints a subscriber-authorization cookie
        // (mercure(..., {subscribe})), which requires a token factory on the hub.
        return new LcobucciFactory('!ChangeThisMercureHubJWTSecretKey!');
    }

    public function publish(Update $update): string
    {
        $this->updates[] = $update;

        return $this->eventId;
    }
}
