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
        $em->persist(Spot::create('Bairon Nautic Club', 'bairon-nautic-club', new Geo(49.60, 4.70), SpotType::FFV_CLUB, postcode: '08380'));
        $em->persist(Spot::create('CNHS', 'cnhs', new Geo(48.85, 2.35), SpotType::FFV_CLUB, postcode: '75004'));
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
        self::assertStringContainsString('<loc>http://localhost/forecast/cnhs</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/forecast/bairon-nautic-club</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/spots/departement/08</loc>', $content);
        self::assertStringContainsString('<loc>http://localhost/spots/departement/75</loc>', $content);
        self::assertStringNotContainsString('/forecast/48.85/2.35', $content);
    }
}
