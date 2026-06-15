<?php

declare(strict_types=1);

namespace App\Ffvoile;

use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Harvests sailing clubs (name + coordinates) from the FFVoile club map
 * (https://carto.ffvoile.fr), department by department.
 *
 * Flow per department: resolve its center via /map/search, list the club ids in a
 * bounding box via /map/clubs/filters (clusters expose their member ids), then resolve
 * those ids to full records via /map/clubs/ids.
 */
final readonly class FfvoileClubScraper
{
    /**
     * FFVoile department codes (metropolitan minus Corsica "20", Corsica 2A/2B, DOM).
     *
     * @var list<string>
     */
    public const array DEPARTMENTS = [
        '01', '02', '03', '04', '05', '06', '07', '08', '09', '10',
        '11', '12', '13', '14', '15', '16', '17', '18', '19', '21',
        '22', '23', '24', '25', '26', '27', '28', '29', '30', '31',
        '32', '33', '34', '35', '36', '37', '38', '39', '40', '41',
        '42', '43', '44', '45', '46', '47', '48', '49', '50', '51',
        '52', '53', '54', '55', '56', '57', '58', '59', '60', '61',
        '62', '63', '64', '65', '66', '67', '68', '69', '70', '71',
        '72', '73', '74', '75', '76', '77', '78', '79', '80', '81',
        '82', '83', '84', '85', '86', '87', '88', '89', '90', '91',
        '92', '93', '94', '95', '2A', '2B', '971', '972', '973', '974', '976',
    ];

    private const float BOX_LAT = 0.8;
    private const float BOX_LNG = 1.2;
    private const int ID_BATCH = 100;

    public function __construct(
        private HttpClientInterface $ffvoileClient,
    ) {
    }

    /**
     * @return list<Club>
     */
    public function fetchDepartment(string $code): array
    {
        $center = $this->searchDepartment($code);
        if (null === $center) {
            return [];
        }

        $ids = $this->clubIds($center);
        if ([] === $ids) {
            return [];
        }

        return $this->resolveClubs($ids, $center);
    }

    /**
     * @return array{lat: float, lng: float, zoom: int}|null
     */
    private function searchDepartment(string $code): ?array
    {
        $data = $this->get('GET', '/map/search/dept_'.$code);

        if (!isset($data['latitude'], $data['longitude'])) {
            return null;
        }

        return [
            'lat' => self::frToFloat($data['latitude']),
            'lng' => self::frToFloat($data['longitude']),
            'zoom' => (int) self::frToFloat($data['zoomLevel'] ?? '9'),
        ];
    }

    /**
     * @param array{lat: float, lng: float, zoom: int} $center
     *
     * @return list<string>
     */
    private function clubIds(array $center): array
    {
        $bounds = [
            'TopLeft' => ['Latitude' => $center['lat'] + self::BOX_LAT, 'Longitude' => $center['lng'] - self::BOX_LNG],
            'BottomRight' => ['Latitude' => $center['lat'] - self::BOX_LAT, 'Longitude' => $center['lng'] + self::BOX_LNG],
        ];
        // The site swaps lat/lng at the top level and inside Center — replicated as-is.
        $swapped = ['Latitude' => $center['lng'], 'Longitude' => $center['lat']];

        $filtersOption = [
            'Bounds' => $bounds,
            'Zoom' => $center['zoom'],
            'Center' => $swapped,
            'DateBegin' => '',
            'DateEnd' => '',
            'MaxPrice' => '',
            'Label' => null,
            'Categories' => [],
        ];
        $clusterRequestOption = [
            'Bounds' => $bounds,
            'Center' => $swapped,
            'MapWidth' => 960,
            'MapHeight' => 562,
            'IconWidth' => 25,
            'IconHeight' => 41,
        ];

        $response = $this->get('POST', '/map/clubs/filters', [
            'json' => [
                'Latitude' => $center['lng'],
                'Longitude' => $center['lat'],
                'Radius' => 150000,
                'Top' => 3,
                // Both option blobs are sent as JSON-encoded strings (server contract).
                'filtersOption' => json_encode($filtersOption, \JSON_THROW_ON_ERROR),
                'clusterRequestOption' => json_encode($clusterRequestOption, \JSON_THROW_ON_ERROR),
            ],
        ]);

        $ids = [];
        foreach ($response as $item) {
            if (!\is_array($item)) {
                continue;
            }
            if (\is_array($item['clusterIds'] ?? null)) {
                foreach ($item['clusterIds'] as $id) {
                    $ids[] = self::str($id);
                }
            } elseif (\is_scalar($item['id'] ?? null)) {
                $ids[] = self::str($item['id']);
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param list<string>                             $ids
     * @param array{lat: float, lng: float, zoom: int} $center
     *
     * @return list<Club>
     */
    private function resolveClubs(array $ids, array $center): array
    {
        $clubs = [];
        foreach (array_chunk($ids, self::ID_BATCH) as $batch) {
            $records = $this->get('POST', '/map/clubs/ids', [
                'json' => [
                    'request' => [
                        'Center' => ['Latitude' => $center['lng'], 'Longitude' => $center['lat']],
                        'Ids' => $batch,
                    ],
                ],
            ]);

            foreach ($records as $record) {
                if (!\is_array($record) || !isset($record['id'], $record['name'], $record['latitude'], $record['longitude'])) {
                    continue;
                }

                $clubs[] = new Club(
                    id: self::str($record['id']),
                    name: self::str($record['name']),
                    latitude: self::flt($record['latitude']),
                    longitude: self::flt($record['longitude']),
                    city: isset($record['city']) ? self::str($record['city']) : null,
                );
            }
        }

        return $clubs;
    }

    /**
     * Performs a request and decodes the JSON body, returning [] on any HTTP/transport/JSON
     * error so a single failing department never aborts a full run.
     *
     * @param array<string, mixed> $options
     *
     * @return array<int|string, mixed>
     */
    private function get(string $method, string $url, array $options = []): array
    {
        try {
            return $this->ffvoileClient->request($method, $url, $options)->toArray();
        } catch (HttpExceptionInterface) {
            return [];
        }
    }

    private static function frToFloat(mixed $value): float
    {
        return self::flt(str_replace(',', '.', self::str($value)));
    }

    private static function str(mixed $value): string
    {
        return \is_scalar($value) ? (string) $value : '';
    }

    private static function flt(mixed $value): float
    {
        return is_numeric($value) ? (float) $value : 0.0;
    }
}
