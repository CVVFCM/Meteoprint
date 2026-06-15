<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum CellSelection: string
{
    case LAND = 'land';
    case SEA = 'sea';
    case NEAREST = 'nearest';
}
