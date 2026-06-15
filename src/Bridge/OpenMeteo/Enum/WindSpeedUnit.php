<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum WindSpeedUnit: string
{
    case KMH = 'kmh';
    case MS = 'ms';
    case MPH = 'mph';
    case KN = 'kn';

    public const self DEFAULT = self::KMH;
}
