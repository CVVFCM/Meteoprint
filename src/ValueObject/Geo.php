<?php

declare(strict_types=1);

namespace App\ValueObject;

final readonly class Geo
{
    public function __construct(
        public float $latitude,
        public float $longitude,
    ) {
    }
}
