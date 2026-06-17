<?php

declare(strict_types=1);

namespace App\Bridge\Ffvoile;

/**
 * FFVoile club identifier.
 */
final readonly class FFVClubId
{
    public function __construct(
        public string $value,
    ) {
    }
}
