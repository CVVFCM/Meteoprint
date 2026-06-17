<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SitemapControllerTest extends WebTestCase
{
    public function testSitemapListsHomepageAndAllSpotForecastUrlsWithCaching(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
        $em->persist(Spot::create('Voile Lyon', 'voile-lyon', new Geo(45.76, 4.83), SpotType::FFV_CLUB));
        $em->persist(Spot::create('Paris Voile', 'paris-voile', new Geo(48.85, 2.35), SpotType::FFV_CLUB));
        $em->flush();

        $client->request('GET', '/sitemap.xml');

        $response = $client->getResponse();
        self::assertResponseIsSuccessful();
        self::assertStringContainsString('application/xml', (string) $response->headers->get('content-type'));
        self::assertTrue((bool) $response->headers->getCacheControlDirective('public'));
        self::assertSame('3600', (string) $response->headers->getCacheControlDirective('max-age'));
        self::assertSame('3600', (string) $response->headers->getCacheControlDirective('s-maxage'));

        $content = (string) $response->getContent();
        self::assertStringContainsString('<loc>http://localhost/</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/forecast/paris-voile</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/forecast/voile-lyon</loc>', $content);
        self::assertStringNotContainsString('/forecast/48.85/2.35', $content);
    }
}
