<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum TimeFormat: string
{
    case ISO8601 = 'iso8601';
    case UNIXTIME = 'unixtime';

    public const DEFAULT = self::ISO8601;
}
