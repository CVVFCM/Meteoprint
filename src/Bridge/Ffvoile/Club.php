<?php

declare(strict_types=1);

namespace App\Bridge\Ffvoile;

/**
 * A sailing club harvested from the FFVoile club map.
 */
final readonly class Club
{
    public function __construct(
        public string $id,
        public string $name,
        public float $latitude,
        public float $longitude,
        public ?string $city = null,
        public ?string $postcode = null,
    ) {
    }
}
