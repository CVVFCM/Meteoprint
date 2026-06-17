<?php

declare(strict_types=1);

namespace App\Twig;

use App\Weather\WeatherCode;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Maps a WMO weather code to its display emoji and translation key for templates.
 */
final class WeatherCodeExtension extends AbstractExtension
{
    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('weather_icon', $this->icon(...)),
            new TwigFilter('weather_label', $this->label(...)),
        ];
    }

    public function icon(int $code, bool $isDay = true): string
    {
        return WeatherCode::tryFromCode($code)->icon($isDay);
    }

    public function label(int $code): string
    {
        return WeatherCode::tryFromCode($code)->translationKey();
    }
}
