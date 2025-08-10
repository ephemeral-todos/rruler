<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;

/**
 * Advanced BYSETPOS scenario compatibility tests.
 *
 * This test class validates advanced BYSETPOS patterns and edge cases
 * against sabre/vobject to ensure RFC 5545 compliance.
 */
final class BySetPosAdvancedTest extends CompatibilityTestCase
{
    /**
     * Test positive and negative BYSETPOS values.
     *
     * Tests various positive and negative positional values.
     */
    public function testPositiveBySetPosValues(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test various positive positions (1st, 2nd, 3rd)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,3;COUNT=6',
            $start,
            6,
            'First, second, and third weekdays of each month'
        );
    }

    public function testNegativeBySetPosValues(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test various negative positions (-1st, -2nd, -3rd)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1,-2,-3;COUNT=6',
            $start,
            6,
            'Last, second-to-last, and third-to-last weekdays of each month'
        );
    }

    public function testMixedPositiveNegativeBySetPos(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test mixing positive and negative positions
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=SA,SU;BYSETPOS=1,-1;COUNT=8',
            $start,
            8,
            'First and last weekend day of each month'
        );
    }

    public function testHighPositiveBySetPos(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test higher positive positions (4th, 5th)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=4,5;COUNT=4',
            $start,
            4,
            'Fourth and fifth weekdays of each month'
        );
    }

    /**
     * Test large occurrence set filtering with BYSETPOS.
     *
     * Tests BYSETPOS performance with large candidate sets.
     */
    public function testLargeOccurrenceSetFiltering(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test with large set of possible dates (all month days)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=1,2,3,4,5,6,7,8,9,10,11,12,13,14,15;BYSETPOS=1,3,5,7,9;COUNT=10',
            $start,
            10,
            'Specific positions from first 15 days of month'
        );
    }

    public function testAllWeekdaysLargeFiltering(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test with all weekdays (large occurrence set per month)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,5,10,15,20;COUNT=10',
            $start,
            10,
            'Specific positions from all weekdays in month'
        );
    }

    /**
     * Test BYSETPOS boundary condition tests.
     *
     * Tests edge cases for first, last, and out-of-bounds positions.
     *
     * ⚠️ WEEKLY BYSETPOS tests will fail due to intentional RFC 5545 compliance difference.
     * See COMPATIBILITY_ISSUES.md for details on weekly BYSETPOS behavior.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testFirstPositionBoundary(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=SA,SU;BYSETPOS=1;COUNT=8',
            $start,
            8,
            'First weekend day of each week (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testLastPositionBoundary(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=SA,SU;BYSETPOS=-1;COUNT=8',
            $start,
            8,
            'Last weekend day of each week (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    public function testOutOfBoundsPositionHandling(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test position that might be out of bounds some months
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=TU;BYSETPOS=5;COUNT=6',
            $start,
            6,
            'Fifth Tuesday of month (when available)'
        );
    }

    public function testExtremeNegativePosition(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test extreme negative position that may not exist
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=WE;BYSETPOS=-5;COUNT=6',
            $start,
            6,
            'Fifth-to-last Wednesday of month (when available)'
        );
    }

    /**
     * Test BYSETPOS with different frequencies.
     *
     * Tests BYSETPOS behavior across different recurrence frequencies.
     */
    #[Group('sabre-dav-incompatibility')]
    public function testBySetPosWithWeeklyFrequency(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=2,4;COUNT=8',
            $start,
            8,
            'Second and fourth weekdays of each week (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    public function testBySetPosWithYearlyFrequency(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Yearly pattern with BYSETPOS
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=1,4,7,10;BYDAY=MO;BYSETPOS=1,-1;COUNT=4',
            $start,
            4,
            'First and last Monday of quarterly months'
        );
    }

    /**
     * Test BYSETPOS performance with intervals.
     *
     * Tests BYSETPOS performance when combined with intervals.
     */
    public function testBySetPosWithInterval(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Every other month with BYSETPOS
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;INTERVAL=2;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,-2,-1;COUNT=8',
            $start,
            8,
            'First two and last two weekdays every other month'
        );
    }

    public function testBySetPosWithLargeInterval(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Every 3 months with specific positions
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;INTERVAL=3;BYDAY=SA,SU;BYSETPOS=1,2;COUNT=4',
            $start,
            4,
            'First two weekend days every 3 months'
        );
    }

    /**
     * Test complex BYSETPOS scenarios.
     *
     * Tests more complex BYSETPOS patterns.
     */
    public function testComplexMultiplePositions(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Multiple non-consecutive positions
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,3,5,7,9,-1;COUNT=12',
            $start,
            12,
            'Specific positions and last weekday of each month'
        );
    }

    public function testBySetPosWithByMonthDayEdgeCase(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // BYSETPOS with BYMONTHDAY edge case
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=28,29,30,31;BYSETPOS=1,-1;COUNT=6',
            $start,
            6,
            'First and last occurrence from month-end dates'
        );
    }

    /**
     * Test BYSETPOS ordering and consistency.
     *
     * Tests that BYSETPOS maintains proper chronological ordering.
     */
    public function testBySetPosChronologicalOrdering(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test that positions are applied in chronological order
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=3,1,5,2,4;COUNT=10',
            $start,
            10,
            'Multiple positions should be chronologically ordered'
        );
    }
}
