<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class RobotsControllerTest extends WebTestCase
{
    public function testRobotsDisallowsNonCanonicalForecastAndUtilityUrls(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        $response = $client->getResponse();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('text/plain', (string) $response->headers->get('content-type'));

        $content = (string) $response->getContent();
        self::assertStringContainsString('Disallow: /forecast/*/*', $content);
        self::assertStringContainsString('Disallow: /geocode', $content);
        self::assertStringContainsString('Disallow: /*?', $content);
        self::assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $content);
    }
}
