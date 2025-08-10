<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;

/**
 * Test time portion preservation in complex yearly patterns.
 *
 * These tests specifically target compatibility issues where time components
 * (hour, minute, second) are being lost during occurrence generation in
 * yearly and complex patterns.
 */
final class TimePortionPreservationTest extends CompatibilityTestCase
{
    /**
     * Test time preservation in simple yearly patterns.
     * This should pass if time portions are preserved correctly.
     */
    public function testYearlyTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-03-15 14:30:45');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;COUNT=3',
            $start,
            3,
            'Yearly pattern should preserve time portions'
        );
    }

    /**
     * Test time preservation in yearly with BYMONTH.
     * Common pattern where time loss occurs.
     */
    public function testYearlyByMonthTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-15 09:15:30');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=1,6,12;COUNT=6',
            $start,
            6,
            'Yearly BYMONTH should preserve time portions'
        );
    }

    /**
     * Test time preservation in yearly with BYMONTHDAY.
     * Another common pattern where time loss occurs.
     */
    public function testYearlyByMonthDayTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-15 16:45:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTHDAY=15;COUNT=4',
            $start,
            4,
            'Yearly BYMONTHDAY should preserve time portions'
        );
    }

    /**
     * Test time preservation in yearly with BYDAY.
     * Complex pattern where time loss is most likely.
     */
    public function testYearlyByDayTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-06 11:20:15'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYDAY=1MO;COUNT=3',
            $start,
            3,
            'Yearly BYDAY should preserve time portions'
        );
    }

    /**
     * Test time preservation in complex yearly patterns.
     * Multiple BY* rules combined - highest chance of time loss.
     */
    public function testComplexYearlyTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-03-03 08:45:30'); // First Monday in March
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=1MO;COUNT=8',
            $start,
            8,
            'Complex yearly pattern should preserve time portions'
        );
    }

    /**
     * Test time preservation in monthly patterns with BYSETPOS.
     * BYSETPOS can cause time portion issues during selection.
     */
    public function testMonthlyBySetPosTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-06 13:30:00'); // First Monday
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=6',
            $start,
            6,
            'Monthly BYSETPOS should preserve time portions'
        );
    }

    /**
     * Test time preservation with seconds precision.
     * Ensures even seconds are not lost in processing.
     */
    public function testSecondsPrecisionPreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-15 14:30:45');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTHDAY=15;COUNT=3',
            $start,
            3,
            'Seconds precision should be preserved'
        );
    }

    /**
     * Test time preservation with midnight times.
     * Edge case where midnight (00:00:00) should be preserved.
     */
    public function testMidnightTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-15 00:00:00');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=1,7;COUNT=4',
            $start,
            4,
            'Midnight times should be preserved'
        );
    }

    /**
     * Test time preservation with late evening times.
     * Edge case to ensure no timezone-related time loss.
     */
    public function testLateEveningTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-15 23:59:59');
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTHDAY=15;COUNT=3',
            $start,
            3,
            'Late evening times should be preserved'
        );
    }

    /**
     * Test time preservation in weekly patterns with BYSETPOS.
     * Ensures weekly patterns also preserve time correctly.
     */
    public function testWeeklyBySetPosTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-03 10:15:30'); // Friday

        // ⚠️ EXPECTED FAILURE: sabre/vobject ignores BYSETPOS for weekly frequencies
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=FR;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS time preservation (RFC 5545 compliant, differs from sabre/vobject)'
        );
    }

    /**
     * Test time preservation in monthly patterns with negative BYSETPOS.
     * Negative positions can cause different code paths that might lose time.
     */
    public function testNegativeBySetPosTimePreservation(): void
    {
        $start = new DateTimeImmutable('2025-01-27 15:45:00'); // Last Monday
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=-1;COUNT=6',
            $start,
            6,
            'Negative BYSETPOS should preserve time portions'
        );
    }
}
