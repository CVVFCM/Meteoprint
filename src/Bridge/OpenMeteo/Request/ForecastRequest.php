<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Request;

use App\Bridge\OpenMeteo\Enum\CellSelection;
use App\Bridge\OpenMeteo\Enum\CurrentVariable;
use App\Bridge\OpenMeteo\Enum\DailyVariable;
use App\Bridge\OpenMeteo\Enum\HourlyVariable;
use App\Bridge\OpenMeteo\Enum\Minutely15Variable;
use App\Bridge\OpenMeteo\Enum\PrecipitationUnit;
use App\Bridge\OpenMeteo\Enum\TemperatureUnit;
use App\Bridge\OpenMeteo\Enum\TimeFormat;
use App\Bridge\OpenMeteo\Enum\WeatherModel;
use App\Bridge\OpenMeteo\Enum\WindSpeedUnit;

/**
 * Fluent builder for a single-location GET /v1/forecast request.
 *
 * Every setter returns $this so calls can be chained. Call toQuery() to obtain the
 * query parameters as the HTTP client expects them.
 */
final class ForecastRequest
{
    /** @var list<HourlyVariable> */
    private array $hourly = [];
    /** @var list<DailyVariable> */
    private array $daily = [];
    /** @var list<CurrentVariable> */
    private array $current = [];
    /** @var list<Minutely15Variable> */
    private array $minutely15 = [];
    /** @var list<WeatherModel> */
    private array $models = [];

    private ?TemperatureUnit $temperatureUnit = null;
    private ?WindSpeedUnit $windSpeedUnit = null;
    private ?PrecipitationUnit $precipitationUnit = null;
    private ?TimeFormat $timeFormat = null;
    private ?CellSelection $cellSelection = null;

    private ?string $timezone = null;
    private ?int $pastDays = null;
    private ?int $forecastDays = null;
    private ?int $pastHours = null;
    private ?int $forecastHours = null;
    private ?string $startDate = null;
    private ?string $endDate = null;
    private ?float $elevation = null;
    private ?float $tilt = null;
    private ?float $azimuth = null;
    private ?string $apiKey = null;

    public function __construct(
        private readonly float $latitude,
        private readonly float $longitude,
    ) {
    }

    public function hourly(HourlyVariable ...$variables): self
    {
        $this->hourly = $variables;

        return $this;
    }

    public function daily(DailyVariable ...$variables): self
    {
        $this->daily = $variables;

        return $this;
    }

    public function current(CurrentVariable ...$variables): self
    {
        $this->current = $variables;

        return $this;
    }

    public function minutely15(Minutely15Variable ...$variables): self
    {
        $this->minutely15 = $variables;

        return $this;
    }

    public function models(WeatherModel ...$models): self
    {
        $this->models = $models;

        return $this;
    }

    public function temperatureUnit(TemperatureUnit $unit): self
    {
        $this->temperatureUnit = $unit;

        return $this;
    }

    public function windSpeedUnit(WindSpeedUnit $unit): self
    {
        $this->windSpeedUnit = $unit;

        return $this;
    }

    public function precipitationUnit(PrecipitationUnit $unit): self
    {
        $this->precipitationUnit = $unit;

        return $this;
    }

    public function timeFormat(TimeFormat $format): self
    {
        $this->timeFormat = $format;

        return $this;
    }

    public function cellSelection(CellSelection $selection): self
    {
        $this->cellSelection = $selection;

        return $this;
    }

    public function timezone(string $timezone): self
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function pastDays(int $days): self
    {
        $this->pastDays = $days;

        return $this;
    }

    public function forecastDays(int $days): self
    {
        $this->forecastDays = $days;

        return $this;
    }

    public function pastHours(int $hours): self
    {
        $this->pastHours = $hours;

        return $this;
    }

    public function forecastHours(int $hours): self
    {
        $this->forecastHours = $hours;

        return $this;
    }

    public function startDate(string $date): self
    {
        $this->startDate = $date;

        return $this;
    }

    public function endDate(string $date): self
    {
        $this->endDate = $date;

        return $this;
    }

    public function elevation(float $elevation): self
    {
        $this->elevation = $elevation;

        return $this;
    }

    public function tilt(float $tilt): self
    {
        $this->tilt = $tilt;

        return $this;
    }

    public function azimuth(float $azimuth): self
    {
        $this->azimuth = $azimuth;

        return $this;
    }

    public function apiKey(string $apiKey): self
    {
        $this->apiKey = $apiKey;

        return $this;
    }

    /**
     * Build the query parameters. Array params are comma-joined because the API uses
     * `explode: false`. Unset parameters are omitted entirely.
     *
     * @return array<string, string|int|float>
     */
    public function toQuery(): array
    {
        $query = [
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
        ];

        if ([] !== $this->hourly) {
            $query['hourly'] = self::join($this->hourly);
        }
        if ([] !== $this->daily) {
            $query['daily'] = self::join($this->daily);
        }
        if ([] !== $this->current) {
            $query['current'] = self::join($this->current);
        }
        if ([] !== $this->minutely15) {
            $query['minutely_15'] = self::join($this->minutely15);
        }
        if ([] !== $this->models) {
            $query['models'] = self::join($this->models);
        }

        if (null !== $this->temperatureUnit) {
            $query['temperature_unit'] = $this->temperatureUnit->value;
        }
        if (null !== $this->windSpeedUnit) {
            $query['wind_speed_unit'] = $this->windSpeedUnit->value;
        }
        if (null !== $this->precipitationUnit) {
            $query['precipitation_unit'] = $this->precipitationUnit->value;
        }
        if (null !== $this->timeFormat) {
            $query['timeformat'] = $this->timeFormat->value;
        }
        if (null !== $this->cellSelection) {
            $query['cell_selection'] = $this->cellSelection->value;
        }

        if (null !== $this->timezone) {
            $query['timezone'] = $this->timezone;
        }
        if (null !== $this->pastDays) {
            $query['past_days'] = $this->pastDays;
        }
        if (null !== $this->forecastDays) {
            $query['forecast_days'] = $this->forecastDays;
        }
        if (null !== $this->pastHours) {
            $query['past_hours'] = $this->pastHours;
        }
        if (null !== $this->forecastHours) {
            $query['forecast_hours'] = $this->forecastHours;
        }
        if (null !== $this->startDate) {
            $query['start_date'] = $this->startDate;
        }
        if (null !== $this->endDate) {
            $query['end_date'] = $this->endDate;
        }
        if (null !== $this->elevation) {
            $query['elevation'] = $this->elevation;
        }
        if (null !== $this->tilt) {
            $query['tilt'] = $this->tilt;
        }
        if (null !== $this->azimuth) {
            $query['azimuth'] = $this->azimuth;
        }
        if (null !== $this->apiKey) {
            $query['apikey'] = $this->apiKey;
        }

        return $query;
    }

    /**
     * @param list<\BackedEnum> $variables
     */
    private static function join(array $variables): string
    {
        return implode(',', array_map(static fn (\BackedEnum $v): string => (string) $v->value, $variables));
    }
}
