<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Response;

/**
 * A time-series block (hourly / daily / current / minutely_15).
 *
 * Holds the `time` axis separately from the variable series, which are keyed by their
 * Open-Meteo variable name. Values are stored raw: with timeformat=iso8601 times are
 * strings, with timeformat=unixtime they are integers.
 */
final class VariableBlock
{
    /**
     * @param list<string|int>            $time
     * @param array<string, mixed> $values series keyed by variable name (current returns scalars, the rest return lists)
     */
    public function __construct(
        private readonly array $time,
        private readonly array $values,
    ) {
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $time = $data['time'] ?? [];
        unset($data['time']);

        $axis = [];
        foreach (\is_array($time) ? $time : [$time] as $point) {
            if (\is_string($point) || \is_int($point)) {
                $axis[] = $point;
            }
        }

        $values = [];
        foreach ($data as $name => $series) {
            if (\is_string($name)) {
                $values[$name] = $series;
            }
        }

        return new self($axis, $values);
    }

    /**
     * @return list<string|int>
     */
    public function time(): array
    {
        return $this->time;
    }

    public function has(\BackedEnum|string $variable): bool
    {
        return \array_key_exists(self::key($variable), $this->values);
    }

    /**
     * Returns the series (or scalar, for the `current` block) for a variable, or null if absent.
     */
    public function get(\BackedEnum|string $variable): mixed
    {
        return $this->values[self::key($variable)] ?? null;
    }

    /**
     * All variable series keyed by name (excludes the `time` axis).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values;
    }

    private static function key(\BackedEnum|string $variable): string
    {
        return $variable instanceof \BackedEnum ? (string) $variable->value : $variable;
    }
}
