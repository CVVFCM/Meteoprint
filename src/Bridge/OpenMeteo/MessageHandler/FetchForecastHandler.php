<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\MessageHandler;

use App\Bridge\OpenMeteo\Enum\HourlyVariable;
use App\Bridge\OpenMeteo\Enum\TimeFormat;
use App\Bridge\OpenMeteo\Enum\WeatherModel;
use App\Bridge\OpenMeteo\Enum\WindSpeedUnit;
use App\Bridge\OpenMeteo\Message\FetchForecast;
use App\Bridge\OpenMeteo\OpenMeteoClient;
use App\Bridge\OpenMeteo\Request\ForecastRequest;
use App\Bridge\OpenMeteo\Response\VariableBlock;
use App\Entity\Forecast as ForecastEntity;
use App\Forecast\ForecastChannel;
use App\Repository\ForecastRepository;
use App\ValueObject\ForecastSlot;
use App\Weather\WeatherCode;
use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Twig\Environment;

#[AsMessageHandler]
final readonly class FetchForecastHandler
{
    /**
     * Hours to keep, in display order.
     *
     * @var list<int>
     */
    private const array HOURS = [9, 12, 15, 19, 0];

    public function __construct(
        private OpenMeteoClient $client,
        private ForecastRepository $repository,
        private HubInterface $hub,
        private Environment $twig,
        private ClockInterface $clock,
    ) {
    }

    public function __invoke(FetchForecast $message): void
    {
        $position = $message->position;
        $day = $message->day;
        // Local calendar date: drives the OpenMeteo day fetch and the displayed day.
        // The Turbo target id is keyed in UTC in the template (matches the page).
        $date = $day->format('Y-m-d');

        $request = (new ForecastRequest($position->latitude, $position->longitude))
            ->models(WeatherModel::METEOFRANCE_SEAMLESS)
            ->hourly(
                HourlyVariable::WEATHER_CODE,
                HourlyVariable::IS_DAY,
                HourlyVariable::TEMPERATURE_2M,
                HourlyVariable::WIND_SPEED_10M,
                HourlyVariable::WIND_DIRECTION_10M,
                HourlyVariable::WIND_GUSTS_10M,
            )
            ->windSpeedUnit(WindSpeedUnit::KN)
            ->timezone('auto')
            ->startDate($date)
            ->endDate($date)
            ->timeFormat(TimeFormat::ISO8601);

        $hourly = $this->client->forecast($request)->hourly;

        if (null === $hourly) {
            return;
        }

        $codes = $hourly->get(HourlyVariable::WEATHER_CODE);
        $isDaySeries = $hourly->get(HourlyVariable::IS_DAY);
        $temperatures = $hourly->get(HourlyVariable::TEMPERATURE_2M);
        $windSpeeds = $hourly->get(HourlyVariable::WIND_SPEED_10M);
        $windDirections = $hourly->get(HourlyVariable::WIND_DIRECTION_10M);
        $windGusts = $hourly->get(HourlyVariable::WIND_GUSTS_10M);

        if (!self::seriesHasNumericValue($codes) || !self::seriesHasNumericValue($isDaySeries)) {
            $fallbackHourly = $this->fetchFallbackWeatherContext($position, $date);
            if (null !== $fallbackHourly) {
                if (!self::seriesHasNumericValue($codes)) {
                    $codes = $fallbackHourly->get(HourlyVariable::WEATHER_CODE);
                }
                if (!self::seriesHasNumericValue($isDaySeries)) {
                    $isDaySeries = $fallbackHourly->get(HourlyVariable::IS_DAY);
                }
            }
        }

        $indexByHour = [];
        foreach ($hourly->time() as $index => $time) {
            if (\is_string($time) && str_starts_with($time, $date)) {
                $indexByHour[(int) substr($time, 11, 2)] = $index;
            }
        }

        $slots = [];
        foreach (self::HOURS as $hour) {
            if (!isset($indexByHour[$hour])) {
                continue;
            }

            $index = $indexByHour[$hour];
            $slots[] = new ForecastSlot(
                hour: $hour,
                weatherCode: self::intValueAt($codes, $index) ?? WeatherCode::UNKNOWN->value,
                isDay: self::boolValueAt($isDaySeries, $index),
                temperature: self::valueAt($temperatures, $index),
                windSpeed: self::valueAt($windSpeeds, $index),
                windDirection: (int) round(self::valueAt($windDirections, $index)),
                windGust: self::valueAt($windGusts, $index),
            );
        }

        $now = $this->clock->now();
        $forecast = $this->repository->findOneForDay($position, $day);

        if (null === $forecast) {
            $forecast = ForecastEntity::create($position, $day, $slots, $now);
        } else {
            $forecast->refresh($slots, $now);
        }

        $this->repository->save($forecast);

        $today = $now->setTime(0, 0);
        $label = $day->format('Ymd') === $today->format('Ymd') ? 'forecast.today' : 'forecast.tomorrow';

        $this->hub->publish(new Update(
            ForecastChannel::topic($position),
            $this->twig->render('forecast/_day.stream.html.twig', [
                'day' => $day,
                'forecast' => $forecast,
                'label' => $label,
            ]),
        ));
    }

    private static function valueAt(mixed $series, int $index): float
    {
        if (\is_array($series) && isset($series[$index]) && is_numeric($series[$index])) {
            return (float) $series[$index];
        }

        return 0.0;
    }

    private static function intValueAt(mixed $series, int $index): ?int
    {
        if (\is_array($series) && isset($series[$index]) && is_numeric($series[$index])) {
            return (int) $series[$index];
        }

        return null;
    }

    private static function boolValueAt(mixed $series, int $index): bool
    {
        return 1 === self::intValueAt($series, $index);
    }

    private static function seriesHasNumericValue(mixed $series): bool
    {
        if (!\is_array($series)) {
            return false;
        }

        foreach ($series as $value) {
            if (is_numeric($value)) {
                return true;
            }
        }

        return false;
    }

    private function fetchFallbackWeatherContext(\App\ValueObject\Geo $position, string $date): ?VariableBlock
    {
        $request = (new ForecastRequest($position->latitude, $position->longitude))
            ->hourly(HourlyVariable::WEATHER_CODE, HourlyVariable::IS_DAY)
            ->timezone('auto')
            ->startDate($date)
            ->endDate($date)
            ->timeFormat(TimeFormat::ISO8601);

        return $this->client->forecast($request)->hourly;
    }
}
