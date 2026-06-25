<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SpotDepartmentControllerTest extends WebTestCase
{
    public function testDepartmentPageListsMatchingSpots(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
        $em->persist(Spot::create('CVVFCM', 'cvvfcm', new Geo(49.87, 4.60), SpotType::FFV_CLUB, postcode: '08170'));
        $em->persist(Spot::create('CDV des Ardennes', 'cdv-ardennes', new Geo(49.75, 4.73), SpotType::FFV_CLUB, postcode: '08000'));
        $em->persist(Spot::create('SNEH', 'sneh', new Geo(16.24, -61.53), SpotType::FFV_CLUB, postcode: '97110'));
        $em->flush();

        $crawler = $client->request('GET', '/spots/departement/08');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('08', trim($crawler->filter('h1')->text()));
        self::assertCount(2, $crawler->filter('.spot-directory__item'));
        self::assertSame(
            ['CDV des Ardennes', 'CVVFCM'],
            $crawler->filter('.spot-directory__link')->each(static fn ($node): string => trim($node->text())),
        );
        self::assertSame(
            ['08000', '08170'],
            $crawler->filter('.spot-directory__postcode')->each(static fn ($node): string => trim($node->text())),
        );
        self::assertSame('http://localhost/spots/departement/08', (string) $crawler->filter('link[rel="canonical"]')->attr('href'));
    }

    public function testUnknownDepartmentIsRejected(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
        $client->request('GET', '/spots/departement/08');

        self::assertResponseStatusCodeSame(404);
    }
}
