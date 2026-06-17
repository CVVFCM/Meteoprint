<?php

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\Spot;
use App\Entity\SpotType;
use App\ValueObject\Geo;
use Doctrine\ORM\EntityManagerInterface;
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
    public function testPlacesGroupWhenNoSpotMatches(): void
    {
        $client = static::createClient();
        $this->clearSpots();
        $this->stubGeocoder();

        $client->request('GET', '/geocode', ['query' => 'Paris']);

        self::assertResponseIsSuccessful();
        $data = $this->decode($client->getResponse()->getContent());

        self::assertSame([
            ['value' => '48.86,2.35', 'text' => 'Paris, Île-de-France, France', 'group_by' => ['Lieux']],
        ], $data['results']['options']);
        self::assertSame([['value' => 'Lieux', 'label' => 'Lieux']], $data['results']['optgroups']);
    }

    public function testSpotsAreListedBeforePlaces(): void
    {
        $client = static::createClient();
        $this->clearSpots();

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->persist(Spot::create('Paris Voile', 'paris-voile', new Geo(48.85, 2.35), SpotType::FFV_CLUB));
        $em->flush();

        $this->stubGeocoder();
        $client->request('GET', '/geocode', ['query' => 'Paris']);

        $data = $this->decode($client->getResponse()->getContent());

        self::assertSame([
            ['value' => 'spot:paris-voile', 'text' => 'Paris Voile', 'group_by' => ['Clubs FFVoile']],
            ['value' => '48.86,2.35', 'text' => 'Paris, Île-de-France, France', 'group_by' => ['Lieux']],
        ], $data['results']['options']);
        self::assertSame([
            ['value' => 'Clubs FFVoile', 'label' => 'Clubs FFVoile'],
            ['value' => 'Lieux', 'label' => 'Lieux'],
        ], $data['results']['optgroups']);
    }

    public function testShortQueryReturnsEmptyGroups(): void
    {
        $client = static::createClient();
        $client->request('GET', '/geocode', ['query' => 'P']);

        self::assertResponseIsSuccessful();
        self::assertSame('{"results":{"options":[],"optgroups":[]}}', $client->getResponse()->getContent());
    }

    private function clearSpots(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->createQuery('DELETE FROM '.Spot::class)->execute();
    }

    private function stubGeocoder(): void
    {
        $aggregator = new ProviderAggregator();
        $aggregator->registerProvider($this->stubProvider());
        static::getContainer()->set(ProviderAggregator::class, $aggregator);
    }

    /**
     * @return array{results: array{options: list<array<string, mixed>>, optgroups: list<array<string, string>>}}
     */
    private function decode(string|false $content): array
    {
        /** @var array{results: array{options: list<array<string, mixed>>, optgroups: list<array<string, string>>}} $data */
        $data = json_decode((string) $content, true, flags: \JSON_THROW_ON_ERROR);

        return $data;
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
                        'adminLevels' => [['level' => 1, 'name' => 'Île-de-France']],
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
