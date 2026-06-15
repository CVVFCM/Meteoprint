<?php

declare(strict_types=1);

namespace App\Tests\OpenMeteo\Request;

use App\Bridge\OpenMeteo\Enum\CurrentVariable;
use App\Bridge\OpenMeteo\Enum\DailyVariable;
use App\Bridge\OpenMeteo\Enum\HourlyVariable;
use App\Bridge\OpenMeteo\Enum\TemperatureUnit;
use App\Bridge\OpenMeteo\Enum\TimeFormat;
use App\Bridge\OpenMeteo\Request\ForecastRequest;
use PHPUnit\Framework\TestCase;

final class ForecastRequestTest extends TestCase
{
    public function testMinimalRequestOnlyHasCoordinates(): void
    {
        $query = (new ForecastRequest(48.85, 2.35))->toQuery();

        self::assertSame(['latitude' => 48.85, 'longitude' => 2.35], $query);
    }

    public function testArrayParamsAreCommaJoined(): void
    {
        $query = (new ForecastRequest(48.85, 2.35))
            ->hourly(HourlyVariable::TEMPERATURE_2M, HourlyVariable::PRECIPITATION)
            ->daily(DailyVariable::TEMPERATURE_2M_MAX)
            ->current(CurrentVariable::WEATHER_CODE)
            ->toQuery();

        self::assertSame([
            'latitude' => 48.85,
            'longitude' => 2.35,
            'hourly' => 'temperature_2m,precipitation',
            'daily' => 'temperature_2m_max',
            'current' => 'weather_code',
        ], $query);
    }

    public function testEnumAndScalarOptionsAreMapped(): void
    {
        $query = (new ForecastRequest(1.0, 2.0))
            ->temperatureUnit(TemperatureUnit::FAHRENHEIT)
            ->timeFormat(TimeFormat::UNIXTIME)
            ->timezone('Europe/Paris')
            ->forecastDays(3)
            ->toQuery();

        self::assertSame([
            'latitude' => 1.0,
            'longitude' => 2.0,
            'temperature_unit' => 'fahrenheit',
            'timeformat' => 'unixtime',
            'timezone' => 'Europe/Paris',
            'forecast_days' => 3,
        ], $query);
    }

    public function testUnsetOptionsAreOmitted(): void
    {
        $query = (new ForecastRequest(1.0, 2.0))->toQuery();

        self::assertArrayNotHasKey('timezone', $query);
        self::assertArrayNotHasKey('forecast_days', $query);
        self::assertArrayNotHasKey('hourly', $query);
    }
}
