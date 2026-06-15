<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum CurrentVariable: string
{
    case TEMPERATURE_2M = 'temperature_2m';
    case RELATIVE_HUMIDITY_2M = 'relative_humidity_2m';
    case APPARENT_TEMPERATURE = 'apparent_temperature';
    case IS_DAY = 'is_day';
    case PRECIPITATION = 'precipitation';
    case RAIN = 'rain';
    case SHOWERS = 'showers';
    case SNOWFALL = 'snowfall';
    case WEATHER_CODE = 'weather_code';
    case CLOUD_COVER = 'cloud_cover';
    case PRESSURE_MSL = 'pressure_msl';
    case SURFACE_PRESSURE = 'surface_pressure';
    case WIND_SPEED_10M = 'wind_speed_10m';
    case WIND_DIRECTION_10M = 'wind_direction_10m';
    case WIND_GUSTS_10M = 'wind_gusts_10m';
}
