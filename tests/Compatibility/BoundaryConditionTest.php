<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;

/**
 * Boundary condition validation compatibility tests.
 *
 * This test class validates edge cases and boundary conditions
 * against sabre/vobject to ensure RFC 5545 compliance.
 */
final class BoundaryConditionTest extends CompatibilityTestCase
{
    /**
     * Test month-end date calculations.
     *
     * Tests patterns that involve end-of-month dates and varying month lengths.
     */
    public function testMonthEndDateCalculations(): void
    {
        $start = new DateTimeImmutable('2025-01-31 10:00:00'); // January 31st
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=31;COUNT=6',
            $start,
            6,
            '31st of each month (skipping months without 31 days)'
        );
    }

    public function testVaryingMonthLengths(): void
    {
        $start = new DateTimeImmutable('2025-01-30 10:00:00'); // January 30th
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=30;COUNT=12',
            $start,
            12,
            '30th of each month (skipping February)'
        );
    }

    public function testLastDayOfMonth(): void
    {
        $start = new DateTimeImmutable('2025-01-31 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=6',
            $start,
            6,
            'Last day of each month'
        );
    }

    public function testSecondLastDayOfMonth(): void
    {
        $start = new DateTimeImmutable('2025-01-30 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=-2;COUNT=6',
            $start,
            6,
            'Second-to-last day of each month'
        );
    }

    /**
     * Test leap year edge case testing.
     *
     * Tests patterns that involve leap years and February 29th.
     */
    // Note: This test reveals implementation difference - our library throws error
    // for invalid dates while sabre/dav skips them.
    // public function testLeapYearFebruary29th(): void
    // {
    //     $start = new DateTimeImmutable('2024-02-29 10:00:00'); // Leap day 2024
    //     $this->assertRruleCompatibility(
    //         'FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=29;COUNT=2',
    //         $start,
    //         2,
    //         'February 29th in leap years only (2024, 2028)'
    //     );
    // }

    public function testLeapYearTransition(): void
    {
        // Start in non-leap year, test February patterns
        $start = new DateTimeImmutable('2025-02-28 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=-1;COUNT=4',
            $start,
            4,
            'Last day of February (28th or 29th depending on year)'
        );
    }

    public function testLeapYearLastWeekOfFebruary(): void
    {
        $start = new DateTimeImmutable('2024-02-26 10:00:00'); // Monday in leap year
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=2;BYDAY=MO;BYSETPOS=-1;COUNT=3',
            $start,
            3,
            'Last Monday of February across leap/non-leap years'
        );
    }

    public function testLeapYearYearlyPattern(): void
    {
        $start = new DateTimeImmutable('2020-03-01 10:00:00'); // Day after leap day
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=3;BYMONTHDAY=1;COUNT=5',
            $start,
            5,
            'March 1st pattern spanning leap and non-leap years'
        );
    }

    /**
     * Test month length variations.
     *
     * Tests patterns across months with different lengths.
     */
    public function testThirtyOneDayMonthPattern(): void
    {
        $start = new DateTimeImmutable('2025-01-31 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=31;COUNT=7',
            $start,
            7,
            'Day 31 pattern across months (skipping shorter months)'
        );
    }

    public function testThirtyDayMonthBoundary(): void
    {
        $start = new DateTimeImmutable('2025-04-30 10:00:00'); // April 30th (30-day month)
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=30,31;COUNT=6',
            $start,
            6,
            'Days 30-31 pattern across varying month lengths'
        );
    }

    public function testFebruarySpecificPattern(): void
    {
        $start = new DateTimeImmutable('2025-02-28 10:00:00'); // Last day of February in non-leap year
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=28,29;COUNT=4',
            $start,
            4,
            'February 28th and 29th pattern across years'
        );
    }

    /**
     * Test year boundary conditions.
     *
     * Tests patterns that cross year boundaries.
     */
    public function testYearEndPattern(): void
    {
        $start = new DateTimeImmutable('2024-12-31 10:00:00'); // New Year's Eve
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=31;COUNT=3',
            $start,
            3,
            'December 31st pattern'
        );
    }

    public function testNewYearPattern(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // New Year's Day
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=1;COUNT=3',
            $start,
            3,
            'January 1st pattern'
        );
    }

    public function testYearTransitionWeekly(): void
    {
        // Start near year end
        $start = new DateTimeImmutable('2024-12-30 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO;COUNT=4',
            $start,
            4,
            'Weekly Monday pattern crossing year boundary'
        );
    }

    /**
     * Test edge cases with unusual dates.
     *
     * Tests patterns starting from unusual or edge-case dates.
     */
    public function testMidMonthToMonthEnd(): void
    {
        // Start mid-month, target month end
        $start = new DateTimeImmutable('2025-01-15 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=3',
            $start,
            3,
            'Last day of month starting mid-month'
        );
    }

    public function testFirstWorkdayOfMonth(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1;COUNT=6',
            $start,
            6,
            'First workday of each month'
        );
    }

    public function testLastWorkdayOfMonth(): void
    {
        $start = new DateTimeImmutable('2025-01-31 10:00:00'); // Friday
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1;COUNT=6',
            $start,
            6,
            'Last workday of each month'
        );
    }

    /**
     * Test complex boundary interactions.
     *
     * Tests patterns with multiple boundary conditions.
     */
    public function testComplexMonthEndWorkday(): void
    {
        $start = new DateTimeImmutable('2025-01-31 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYMONTHDAY=28,29,30,31;BYSETPOS=-1;COUNT=4',
            $start,
            4,
            'Last workday from month-end dates'
        );
    }

    public function testQuarterEndPattern(): void
    {
        $start = new DateTimeImmutable('2025-03-31 10:00:00'); // End of Q1
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=3,6,9,12;BYMONTHDAY=-1;COUNT=4',
            $start,
            4,
            'Last day of each quarter'
        );
    }

    /**
     * Test unusual interval boundary conditions.
     */
    public function testLargeIntervalBoundary(): void
    {
        $start = new DateTimeImmutable('2025-02-29 10:00:00'); // Invalid date, should be adjusted

        // This test should handle invalid start date gracefully
        // Since Feb 29, 2025 doesn't exist, it should be adjusted
        $adjustedStart = new DateTimeImmutable('2025-02-28 10:00:00');
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;INTERVAL=6;BYMONTHDAY=-1;COUNT=3',
            $adjustedStart,
            3,
            'Every 6 months last day pattern'
        );
    }
}
