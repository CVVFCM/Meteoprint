<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Origin/kind of a saved {@see Spot}.
 */
enum SpotType: string
{
    case FFV_CLUB = 'ffv_club';

    public function label(): string
    {
        return 'spot.type.'.$this->value;
    }
}
