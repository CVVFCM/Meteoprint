<?php

declare(strict_types=1);

namespace App\Tests\Bridge\Ffvoile;

use App\Bridge\Ffvoile\FfvoileClubScraper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class FfvoileClubScraperTest extends TestCase
{
    public function testFetchDepartmentResolvesClustersToClubs(): void
    {
        $client = new MockHttpClient(function (string $method, string $url): ResponseInterface {
            if (str_contains($url, '/map/search/dept_08')) {
                return self::json(['latitude' => '49,6980117', 'longitude' => '4,6716005', 'zoomLevel' => '9']);
            }

            if (str_contains($url, '/map/clubs/filters')) {
                return self::json([
                    ['isCluster' => true, 'clusterIds' => ['08000', '08002'], 'id' => null],
                    ['isCluster' => false, 'id' => '08007'],
                ]);
            }

            if (str_contains($url, '/map/clubs/ids')) {
                return self::json([
                    ['id' => '08000', 'name' => 'CDV DES ARDENNES', 'latitude' => 49.752, 'longitude' => 4.731, 'city' => 'CHARLEVILLE MEZIERES', 'postalCode' => '08000'],
                    ['id' => '08002', 'name' => 'CVVFCM', 'latitude' => 49.873, 'longitude' => 4.595, 'city' => 'VRESSE', 'postalCode' => '08170'],
                    ['id' => '08007', 'name' => 'BAIRON NAUTIC CLUB', 'latitude' => 49.527, 'longitude' => 4.781, 'city' => 'LES AYVELLES', 'postalCode' => '08000'],
                ]);
            }

            return new MockResponse('[]', ['response_headers' => ['content-type' => 'application/json']]);
        });

        $clubs = (new FfvoileClubScraper($client))->fetchDepartment('08');

        self::assertCount(3, $clubs);
        self::assertSame('08000', $clubs[0]->id);
        self::assertSame('CDV DES ARDENNES', $clubs[0]->name);
        self::assertSame(49.752, $clubs[0]->latitude);
        self::assertSame(4.731, $clubs[0]->longitude);
        self::assertSame('CHARLEVILLE MEZIERES', $clubs[0]->city);
        self::assertSame('08000', $clubs[0]->postcode);
    }

    public function testUnknownDepartmentYieldsNoClubs(): void
    {
        $client = new MockHttpClient(static fn (): ResponseInterface => self::json([]));

        self::assertSame([], (new FfvoileClubScraper($client))->fetchDepartment('99'));
    }

    /**
     * @param array<mixed> $data
     */
    private static function json(array $data): MockResponse
    {
        return new MockResponse(json_encode($data, \JSON_THROW_ON_ERROR), [
            'response_headers' => ['content-type' => 'application/json'],
        ]);
    }
}
