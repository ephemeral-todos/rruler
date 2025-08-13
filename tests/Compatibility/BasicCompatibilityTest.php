<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\TestCase\CompatibilityTestCase;

/**
 * Basic compatibility tests to verify the infrastructure works correctly.
 */
final class BasicCompatibilityTest extends CompatibilityTestCase
{
    public function testSimpleDailyCompatibility(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=DAILY;COUNT=3',
            $start,
            3,
            'Simple daily pattern'
        );
    }

    public function testWeeklyCompatibility(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;COUNT=4',
            $start,
            4,
            'Weekly pattern'
        );
    }

    public function testMonthlyCompatibility(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;COUNT=6',
            $start,
            6,
            'Monthly pattern'
        );
    }

    public function testByDayCompatibility(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6',
            $start,
            6,
            'Weekly with BYDAY'
        );
    }

    public function testBySetPosCompatibility(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=3',
            $start,
            3,
            'First Monday of each month'
        );
    }
}
