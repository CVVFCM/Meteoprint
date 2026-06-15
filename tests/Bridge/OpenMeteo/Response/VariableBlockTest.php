<?php

declare(strict_types=1);

namespace App\Tests\OpenMeteo\Response;

use App\Bridge\OpenMeteo\Enum\HourlyVariable;
use App\Bridge\OpenMeteo\Response\VariableBlock;
use PHPUnit\Framework\TestCase;

final class VariableBlockTest extends TestCase
{
    public function testSplitsTimeFromVariables(): void
    {
        $block = VariableBlock::fromArray([
            'time' => ['2026-06-15T00:00', '2026-06-15T01:00'],
            'temperature_2m' => [12.3, 11.8],
        ]);

        self::assertSame(['2026-06-15T00:00', '2026-06-15T01:00'], $block->time());
        self::assertSame([12.3, 11.8], $block->get(HourlyVariable::TEMPERATURE_2M));
        self::assertSame([12.3, 11.8], $block->get('temperature_2m'));
        self::assertArrayNotHasKey('time', $block->all());
    }

    public function testHasReportsPresence(): void
    {
        $block = VariableBlock::fromArray(['time' => [], 'temperature_2m' => []]);

        self::assertTrue($block->has(HourlyVariable::TEMPERATURE_2M));
        self::assertFalse($block->has(HourlyVariable::PRECIPITATION));
        self::assertNull($block->get('precipitation'));
    }

    public function testScalarTimeIsWrapped(): void
    {
        $block = VariableBlock::fromArray(['time' => '2026-06-15T12:00', 'temperature_2m' => 18.0]);

        self::assertSame(['2026-06-15T12:00'], $block->time());
        self::assertSame(18.0, $block->get('temperature_2m'));
    }
}
