<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use Geocoder\Collection;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Provider\Provider;
use Geocoder\ProviderAggregator;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class GeocodeControllerTest extends WebTestCase
{
    public function testReturnsAutocompleteResultsFromGeocoder(): void
    {
        $client = static::createClient();

        $aggregator = new ProviderAggregator();
        $aggregator->registerProvider($this->stubProvider());
        static::getContainer()->set(ProviderAggregator::class, $aggregator);

        $client->request('GET', '/geocode', ['query' => 'Paris']);

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');

        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR);
        self::assertSame([
            'results' => [
                ['value' => '48.856600,2.352200', 'text' => 'Paris, Île-de-France, France'],
            ],
        ], $data);
    }

    public function testShortQueryReturnsEmptyResults(): void
    {
        $client = static::createClient();
        $client->request('GET', '/geocode', ['query' => 'P']);

        self::assertResponseIsSuccessful();
        self::assertSame('{"results":[]}', $client->getResponse()->getContent());
    }

    private function stubProvider(): Provider
    {
        return new class implements Provider {
            public function geocodeQuery(GeocodeQuery $query): Collection
            {
                return new AddressCollection([
                    Address::createFromArray([
                        'latitude' => 48.8566,
                        'longitude' => 2.3522,
                        'locality' => 'Paris',
                        'country' => 'France',
                        'adminLevels' => [
                            ['level' => 1, 'name' => 'Île-de-France'],
                        ],
                    ]),
                ]);
            }

            public function reverseQuery(ReverseQuery $query): Collection
            {
                return new AddressCollection([]);
            }

            public function getName(): string
            {
                return 'stub';
            }
        };
    }
}
