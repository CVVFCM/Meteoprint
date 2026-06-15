<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ForecastControllerTest extends WebTestCase
{
    public function testForecastPageRendersForLatLonPath(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forecast/48.85/2.35');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', '48.85');
        self::assertSelectorTextContains('body', '2.35');
    }

    public function testNonNumericCoordinatesAreRejected(): void
    {
        $client = static::createClient();
        $client->request('GET', '/forecast/paris/london');

        self::assertResponseStatusCodeSame(404);
    }
}
