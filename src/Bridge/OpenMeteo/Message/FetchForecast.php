<?php

declare(strict_types=1);

namespace App\Bridge\OpenMeteo\Message;

use App\ValueObject\Geo;

/**
 * Request to fetch and persist the Arome-HD forecast for one location and one day.
 */
final readonly class FetchForecast
{
    public function __construct(
        public Geo $position,
        public \DateTimeImmutable $day,
    ) {
    }
}
