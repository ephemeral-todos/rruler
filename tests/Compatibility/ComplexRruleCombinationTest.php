<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Group;

/**
 * Complex RRULE combination compatibility tests.
 *
 * This test class validates complex combinations of RRULE parameters
 * against sabre/vobject to ensure RFC 5545 compliance for advanced patterns.
 */
final class ComplexRruleCombinationTest extends CompatibilityTestCase
{
    /**
     * Test BYDAY + BYSETPOS combinations with different frequencies.
     *
     * These are well-established patterns that should work reliably.
     */
    public function testMonthlyByDayWithBySetPosVariations(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // First and last Tuesday of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=TU;BYSETPOS=1,-1;COUNT=6',
            $start,
            6,
            'First and last Tuesday of each month'
        );

        // Validate against python-dateutil fixture
        $this->assertPythonDateutilFixtureCompatibility(
            'complex_bysetpos_patterns',
            'Complex positional filtering'
        );
    }

    public function testMonthlyByDayWithBySetPosMultiple(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Second and third Friday of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=2,3;COUNT=6',
            $start,
            6,
            'Second and third Friday of each month'
        );

        // Validate against python-dateutil fixture
        $this->assertPythonDateutilFixtureCompatibility(
            'complex_bysetpos_patterns',
            'Multiple consecutive positions'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testWeeklyByDayWithBySetPosFirst(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=8',
            $start,
            8,
            'First occurrence from Mon/Wed/Fri each week (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    #[Group('sabre-dav-incompatibility')]
    public function testWeeklyByDayWithBySetPosLast(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=-1;COUNT=8',
            $start,
            8,
            'Last occurrence from Mon/Wed/Fri each week (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    /**
     * Test BYDAY + BYMONTHDAY + BYSETPOS combinations.
     *
     * Tests complex filtering with weekday, month day, and positional constraints.
     */
    public function testByDayByMonthDayWithBySetPosFirst(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // First Monday or Tuesday from first 5 days of month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU;BYMONTHDAY=1,2,3,4,5;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'First Monday or Tuesday from the first 5 days of each month'
        );
    }

    public function testByDayByMonthDayWithBySetPosLast(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Last weekday from last 5 days of month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYMONTHDAY=-5,-4,-3,-2,-1;BYSETPOS=-1;COUNT=3',
            $start,
            3,
            'Last weekday from the last 5 days of each month'
        );
    }

    /**
     * Test BYMONTH + BYDAY + BYSETPOS combinations.
     *
     * Tests yearly patterns with month, weekday, and positional filtering.
     */
    public function testYearlyByMonthByDayWithBySetPosFirst(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // First Monday of March, June, September, December each year
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=MO;BYSETPOS=1;COUNT=8',
            $start,
            8,
            'First Monday of quarterly months'
        );
    }

    public function testYearlyByMonthByDayWithBySetPosLast(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Last Friday of March and September each year
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=3,9;BYDAY=FR;BYSETPOS=-1;COUNT=6',
            $start,
            6,
            'Last Friday of March and September'
        );
    }

    /**
     * Test BYMONTHDAY + BYSETPOS combinations.
     *
     * Tests positional filtering of month days.
     */
    public function testByMonthDayWithBySetPosFirst(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // First occurrence from 1st or 15th of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=1,15;BYSETPOS=1;COUNT=6',
            $start,
            6,
            'First occurrence from 1st or 15th of each month (always 1st)'
        );
    }

    public function testByMonthDayWithBySetPosLast(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Last occurrence from 1st or 15th of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=1,15;BYSETPOS=-1;COUNT=6',
            $start,
            6,
            'Last occurrence from 1st or 15th of each month (always 15th)'
        );
    }

    /**
     * Test complex parameter interactions with intervals.
     *
     * Tests combinations with non-default intervals.
     */
    public function testComplexCombinationWithInterval(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Second Tuesday every 2 months
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;INTERVAL=2;BYDAY=TU;BYSETPOS=2;COUNT=4',
            $start,
            4,
            'Second Tuesday every other month'
        );

        // Validate against python-dateutil fixture
        $this->assertPythonDateutilFixtureCompatibility(
            'interval_combinations',
            'Bi-monthly with positional filtering'
        );
    }

    /**
     * Test boundary conditions in complex combinations.
     *
     * Tests edge cases with unusual start dates.
     */
    public function testComplexCombinationWithMidMonthStart(): void
    {
        // Test with start date that doesn't match the pattern
        $start = new DateTimeImmutable('2025-02-15 10:00:00'); // Saturday in February

        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=3',
            $start,
            3,
            'First Monday starting mid-February'
        );
    }

    public function testComplexCombinationWithYearBoundary(): void
    {
        // Test starting near year end
        $start = new DateTimeImmutable('2024-12-15 10:00:00');

        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1;COUNT=4',
            $start,
            4,
            'Last Friday starting in December, crossing year boundary'
        );
    }
}
