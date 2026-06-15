<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Forecast;
use App\Message\FetchForecast;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;

final class ForecastControllerTest extends WebTestCase
{
    public function testRendersTwoLoadingColumnsAndDispatchesPerDay(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Forecast::class)->execute();

        $crawler = $client->request('GET', '/forecast/48.85/2.35');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('turbo-mercure-stream-source'));
        self::assertCount(2, $crawler->filter('.day'));
        self::assertCount(2, $crawler->filter('.day__loading'));

        $transport = static::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);
        $sent = $transport->getSent();
        self::assertCount(2, $sent, 'one FetchForecast dispatched per missing day');
        foreach ($sent as $envelope) {
            self::assertInstanceOf(FetchForecast::class, $envelope->getMessage());
        }
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
}
