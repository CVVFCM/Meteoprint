<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Enum;

enum Minutely15Variable: string
{
    case TEMPERATURE_2M = 'temperature_2m';
    case RELATIVE_HUMIDITY_2M = 'relative_humidity_2m';
    case DEW_POINT_2M = 'dew_point_2m';
    case APPARENT_TEMPERATURE = 'apparent_temperature';
    case PRECIPITATION = 'precipitation';
    case RAIN = 'rain';
    case SNOWFALL = 'snowfall';
    case SNOWFALL_HEIGHT = 'snowfall_height';
    case FREEZING_LEVEL_HEIGHT = 'freezing_level_height';
    case SUNSHINE_DURATION = 'sunshine_duration';
    case WEATHER_CODE = 'weather_code';
    case WIND_SPEED_10M = 'wind_speed_10m';
    case WIND_SPEED_20M = 'wind_speed_20m';
    case WIND_SPEED_50M = 'wind_speed_50m';
    case WIND_SPEED_80M = 'wind_speed_80m';
    case WIND_SPEED_100M = 'wind_speed_100m';
    case WIND_DIRECTION_10M = 'wind_direction_10m';
    case WIND_DIRECTION_20M = 'wind_direction_20m';
    case WIND_DIRECTION_50M = 'wind_direction_50m';
    case WIND_DIRECTION_80M = 'wind_direction_80m';
    case WIND_DIRECTION_100M = 'wind_direction_100m';
    case WIND_GUSTS_10M = 'wind_gusts_10m';
    case VISIBILITY = 'visibility';
    case CAPE = 'cape';
    case LIGHTNING_POTENTIAL = 'lightning_potential';
    case IS_DAY = 'is_day';
}
