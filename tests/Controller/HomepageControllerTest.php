<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomepageControllerTest extends WebTestCase
{
    public function testHomepageRendersAutocompleteField(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        // The UX Autocomplete TextType renders an autocomplete-controlled <input>.
        self::assertCount(1, $crawler->filter('input[name="place_search[place]"][data-controller~="symfony--ux-autocomplete--autocomplete"]'));
        self::assertStringContainsString('Meteoprint', trim($crawler->filter('title')->text()));
        self::assertNotSame('', (string) $crawler->filter('meta[name="description"]')->attr('content'));
        self::assertSame('index,follow', (string) $crawler->filter('meta[name="robots"]')->attr('content'));
        self::assertSame('http://localhost/', (string) $crawler->filter('link[rel="canonical"]')->attr('href'));
        self::assertSame('website', (string) $crawler->filter('meta[property="og:type"]')->attr('content'));
        self::assertSame('http://localhost/', (string) $crawler->filter('meta[property="og:url"]')->attr('content'));
        self::assertStringContainsString('Meteoprint', (string) $crawler->filter('meta[property="og:title"]')->attr('content'));
        self::assertNotSame('', (string) $crawler->filter('meta[property="og:description"]')->attr('content'));
    }

    public function testValidSelectionRedirectsToForecast(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', ['place_search' => ['place' => '48.853000,2.349000']]);

        // Coordinates are rounded to 2 decimals to keep forecast URLs stable and tidy.
        self::assertResponseRedirects('/forecast/48.85/2.35');
    }

    public function testSpotSelectionRedirectsToCanonicalSlugForecast(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
        $em->persist(Spot::create('CVVFCM', 'cvvfcm', new Geo(49.87, 4.60), SpotType::FFV_CLUB, postcode: '08170'));
        $em->persist(Spot::create('CNHS', 'cnhs', new Geo(48.85, 2.35), SpotType::FFV_CLUB, postcode: '75004'));
        $em->flush();

        $client->request('GET', '/', ['place_search' => ['place' => 'spot:cvvfcm']]);

        self::assertResponseRedirects('/forecast/cvvfcm');
    }

    public function testInvalidSelectionDoesNotRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', ['place_search' => ['place' => 'not-a-coordinate']]);

        // Symfony renders an invalid submitted form as 422 (not a redirect).
        self::assertResponseStatusCodeSame(422);
    }
}
