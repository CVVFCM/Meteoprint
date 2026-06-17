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
    }

    public function testValidSelectionRedirectsToForecast(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', ['place_search' => ['place' => '48.853000,2.349000']]);

        // Coordinates are rounded to 2 decimals (Arome HD resolution).
        self::assertResponseRedirects('/forecast/48.85/2.35');
    }

    public function testSpotSelectionRedirectsToCanonicalSlugForecast(): void
    {
        $client = static::createClient();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
        $em->persist(Spot::create('Paris Voile', 'paris-voile', new Geo(48.85, 2.35), SpotType::FFV_CLUB));
        $em->flush();

        $client->request('GET', '/', ['place_search' => ['place' => 'spot:paris-voile']]);

        self::assertResponseRedirects('/forecast/paris-voile');
    }

    public function testInvalidSelectionDoesNotRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', ['place_search' => ['place' => 'not-a-coordinate']]);

        // Symfony renders an invalid submitted form as 422 (not a redirect).
        self::assertResponseStatusCodeSame(422);
    }
}
