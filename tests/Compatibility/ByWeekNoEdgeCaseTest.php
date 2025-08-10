<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;

/**
 * BYWEEKNO edge case compatibility tests.
 *
 * This test class validates edge cases and boundary conditions for BYWEEKNO
 * patterns against sabre/vobject to ensure RFC 5545 compliance.
 *
 * ⚠️  IMPORTANT: These tests document intentional differences from sabre/dav.
 *
 * sabre/dav has bugs in its BYWEEKNO implementation for yearly frequencies where
 * it returns incorrect dates and doesn't properly handle week boundaries.
 *
 * Rruler correctly implements RFC 5545 BYWEEKNO behavior, validated against
 * python-dateutil (the gold standard). These tests will fail when comparing against
 * sabre/dav, which is expected and correct.
 */
final class ByWeekNoEdgeCaseTest extends CompatibilityTestCase
{
    /**
     * Test week number patterns across year boundaries.
     *
     * Tests patterns that span across year boundaries, including week 1 and week 52/53.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testYearBoundaryWeekOnePattern(): void
    {
        // Week 1 typically spans from late December to early January
        $start = new DateTimeImmutable('2024-12-30 10:00:00'); // Monday of week 1 in 2025
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=1;COUNT=3',
            $start,
            3,
            'Week 1 pattern across year boundaries'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testYearBoundaryWeekFiftyTwoPattern(): void
    {
        // Week 52 typically spans into the next year
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=52;COUNT=3',
            $start,
            3,
            'Week 52 pattern across year boundaries'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testConsecutiveYearBoundaryWeeks(): void
    {
        // Test consecutive weeks that cross year boundary
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=52,1;COUNT=4',
            $start,
            4,
            'Consecutive weeks 52 and 1 crossing year boundary'
        );
    }

    /**
     * Test leap year BYWEEKNO boundary conditions.
     *
     * Tests patterns in leap years and their interaction with week numbering.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testLeapYearWeekNumbering(): void
    {
        // 2024 is a leap year
        $start = new DateTimeImmutable('2024-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=26;COUNT=3',
            $start,
            3,
            'Week 26 pattern starting in leap year'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testLeapYearWeekFiftyThree(): void
    {
        // 2020 has 53 weeks, next occurrence is 2026
        $start = new DateTimeImmutable('2020-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=53;COUNT=2',
            $start,
            2,
            'Week 53 pattern in leap week years'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testLeapYearTransitionWeek(): void
    {
        // Test February 29th week in leap year
        $start = new DateTimeImmutable('2024-02-26 10:00:00'); // Week 9 in 2024
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=9;COUNT=3',
            $start,
            3,
            'Week 9 pattern around leap day (Feb 29)'
        );
    }

    /**
     * Test ISO 8601 week numbering validation.
     *
     * Tests patterns that validate ISO 8601 compliance for week numbering.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testIso8601Week1Definition(): void
    {
        // ISO 8601: Week 1 is the first week with at least 4 days in the new year
        // Starting from a Thursday (4th day of week) to ensure proper week 1
        $start = new DateTimeImmutable('2025-01-02 10:00:00'); // Thursday
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=1;COUNT=3',
            $start,
            3,
            'ISO 8601 week 1 definition validation'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testIso8601LastWeekDefinition(): void
    {
        // ISO 8601: Last week of year depends on January 1st day
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=52;COUNT=3',
            $start,
            3,
            'ISO 8601 last week definition validation'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testIso8601MondayWeekStart(): void
    {
        // ISO 8601 specifies Monday as the first day of the week
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=2;COUNT=3',
            $start,
            3,
            'ISO 8601 Monday week start validation'
        );
    }

    /**
     * Test complex year boundary scenarios.
     *
     * Tests more complex scenarios involving year boundaries.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testMultipleYearBoundaryWeeks(): void
    {
        // Test multiple weeks around year boundary
        $start = new DateTimeImmutable('2024-12-20 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=51,52,1,2;COUNT=8',
            $start,
            8,
            'Multiple weeks around year boundary'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testYearBoundaryWithInterval(): void
    {
        // Every other year, week 1
        $start = new DateTimeImmutable('2024-12-30 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;INTERVAL=2;BYWEEKNO=1;COUNT=3',
            $start,
            3,
            'Year boundary week 1 with interval'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testYearBoundaryWithCount(): void
    {
        // Test COUNT termination across year boundaries
        $start = new DateTimeImmutable('2024-12-28 10:00:00'); // Saturday
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=52;COUNT=2',
            $start,
            2,
            'Year boundary with COUNT termination'
        );
    }

    /**
     * Test edge cases with unusual start dates.
     *
     * Tests patterns starting from dates that don't align with week boundaries.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testMidWeekStartYearBoundary(): void
    {
        // Start in middle of week that crosses year boundary
        $start = new DateTimeImmutable('2024-12-31 15:30:00'); // Tuesday afternoon
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=1;COUNT=3',
            $start,
            3,
            'Mid-week start across year boundary'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testWeekendStartYearBoundary(): void
    {
        // Start on weekend during year boundary week
        $start = new DateTimeImmutable('2025-01-04 10:00:00'); // Saturday
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYWEEKNO=1;COUNT=3',
            $start,
            3,
            'Weekend start during year boundary week'
        );
    }
}
