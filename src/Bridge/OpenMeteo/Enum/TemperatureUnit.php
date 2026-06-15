<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum TemperatureUnit: string
{
    case CELSIUS = 'celsius';
    case FAHRENHEIT = 'fahrenheit';

    public const DEFAULT = self::CELSIUS;
}
