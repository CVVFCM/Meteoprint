<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum PrecipitationUnit: string
{
    case MM = 'mm';
    case INCH = 'inch';

    public const self DEFAULT = self::MM;
}
