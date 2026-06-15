<?php

declare(strict_types=1);

namespace App\Tests\Controller;

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

    public function testInvalidSelectionDoesNotRedirect(): void
    {
        $client = static::createClient();
        $client->request('GET', '/', ['place_search' => ['place' => 'not-a-coordinate']]);

        // Symfony renders an invalid submitted form as 422 (not a redirect).
        self::assertResponseStatusCodeSame(422);
    }
}
