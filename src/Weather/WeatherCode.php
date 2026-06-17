<?php

declare(strict_types=1);

namespace App\Weather;

/**
 * WMO weather interpretation codes used by Open-Meteo, with a display emoji and a
 * translation key. Unknown codes degrade gracefully via {@see tryFromCode()}.
 *
 * @see https://open-meteo.com/en/docs (WMO Weather interpretation codes)
 */
enum WeatherCode: int
{
    case UNKNOWN = -1;
    case CLEAR = 0;
    case MAINLY_CLEAR = 1;
    case PARTLY_CLOUDY = 2;
    case OVERCAST = 3;
    case FOG = 45;
    case DEPOSITING_RIME_FOG = 48;
    case DRIZZLE_LIGHT = 51;
    case DRIZZLE_MODERATE = 53;
    case DRIZZLE_DENSE = 55;
    case FREEZING_DRIZZLE_LIGHT = 56;
    case FREEZING_DRIZZLE_DENSE = 57;
    case RAIN_SLIGHT = 61;
    case RAIN_MODERATE = 63;
    case RAIN_HEAVY = 65;
    case FREEZING_RAIN_LIGHT = 66;
    case FREEZING_RAIN_HEAVY = 67;
    case SNOW_SLIGHT = 71;
    case SNOW_MODERATE = 73;
    case SNOW_HEAVY = 75;
    case SNOW_GRAINS = 77;
    case RAIN_SHOWERS_SLIGHT = 80;
    case RAIN_SHOWERS_MODERATE = 81;
    case RAIN_SHOWERS_VIOLENT = 82;
    case SNOW_SHOWERS_SLIGHT = 85;
    case SNOW_SHOWERS_HEAVY = 86;
    case THUNDERSTORM = 95;
    case THUNDERSTORM_SLIGHT_HAIL = 96;
    case THUNDERSTORM_HEAVY_HAIL = 99;

    public function icon(bool $isDay = true): string
    {
        return match ($this) {
            self::UNKNOWN => '·',
            self::CLEAR => $isDay ? '☀️' : '🌙',
            self::MAINLY_CLEAR => $isDay ? '🌤️' : '🌙',
            self::PARTLY_CLOUDY => $isDay ? '⛅' : '☁️',
            self::OVERCAST => '☁️',
            self::FOG, self::DEPOSITING_RIME_FOG => '🌫️',
            self::DRIZZLE_LIGHT, self::DRIZZLE_MODERATE, self::DRIZZLE_DENSE,
            self::FREEZING_DRIZZLE_LIGHT, self::FREEZING_DRIZZLE_DENSE => '🌦️',
            self::RAIN_SLIGHT, self::RAIN_MODERATE, self::RAIN_HEAVY,
            self::FREEZING_RAIN_LIGHT, self::FREEZING_RAIN_HEAVY,
            self::RAIN_SHOWERS_SLIGHT, self::RAIN_SHOWERS_MODERATE, self::RAIN_SHOWERS_VIOLENT => '🌧️',
            self::SNOW_SLIGHT, self::SNOW_MODERATE, self::SNOW_HEAVY, self::SNOW_GRAINS,
            self::SNOW_SHOWERS_SLIGHT, self::SNOW_SHOWERS_HEAVY => '❄️',
            self::THUNDERSTORM, self::THUNDERSTORM_SLIGHT_HAIL, self::THUNDERSTORM_HEAVY_HAIL => '⛈️',
        };
    }

    public function translationKey(): string
    {
        return match ($this) {
            self::UNKNOWN => 'weather.code.unknown',
            default => 'weather.code.'.$this->value,
        };
    }

    public static function tryFromCode(?int $code): self
    {
        return null !== $code ? (self::tryFrom($code) ?? self::UNKNOWN) : self::UNKNOWN;
    }
}
