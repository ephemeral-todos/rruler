<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;

/**
 * Comprehensive tests documenting weekly BYSETPOS behavior differences between
 * Rruler and sabre/dav implementations.
 *
 * ⚠️  IMPORTANT: These tests document an intentional difference from sabre/dav.
 *
 * sabre/dav has a bug where it completely ignores BYSETPOS for weekly frequencies,
 * treating FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1 identically to FREQ=WEEKLY;BYDAY=MO,WE,FR.
 *
 * Rruler correctly implements RFC 5545 weekly BYSETPOS behavior, validated against
 * python-dateutil (the gold standard). These tests will fail when comparing against
 * sabre/dav, which is expected and correct.
 *
 * See COMPATIBILITY_ISSUES.md for detailed documentation of this difference.
 */
final class WeeklyBySetPosBehaviorTest extends CompatibilityTestCase
{
    /**
     * Document the sabre/dav weekly BYSETPOS bug.
     * This test demonstrates that sabre/dav ignores BYSETPOS for weekly patterns.
     */
    public function testSabreDavWeeklyBySetPosBugDocumentation(): void
    {
        $this->markTestSkipped(
            'sabre/dav has a confirmed bug where it ignores BYSETPOS for weekly frequencies. '.
            'Rruler implements correct RFC 5545 behavior validated against python-dateutil. '.
            'This intentional difference is documented in COMPATIBILITY_ISSUES.md'
        );
    }

    /**
     * Test basic weekly BYSETPOS=1 (first occurrence of week).
     *
     * ⚠️  EXPECTED TO FAIL: sabre/dav ignores BYSETPOS for weekly patterns.
     * Rruler correctly implements RFC 5545 behavior.
     */
    public function testWeeklyBySetPosFirst(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'First occurrence of Mon/Wed/Fri each week'
        );
    }

    /**
     * Test basic weekly BYSETPOS=-1 (last occurrence of week).
     *
     * ⚠️  EXPECTED TO FAIL: sabre/dav ignores BYSETPOS for weekly patterns.
     * Rruler correctly implements RFC 5545 behavior.
     */
    public function testWeeklyBySetPosLast(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=-1;COUNT=4',
            $start,
            4,
            'Last occurrence of Mon/Wed/Fri each week'
        );
    }

    /**
     * Test weekly BYSETPOS=2 (second occurrence of week).
     * This tests middle position selection logic.
     */
    public function testWeeklyBySetPosSecond(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=2;COUNT=4',
            $start,
            4,
            'Second occurrence of Mon/Wed/Fri each week'
        );
    }

    /**
     * Test weekly BYSETPOS with single weekday.
     * This should behave like normal weekly without BYSETPOS.
     */
    public function testWeeklyBySetPosSingleWeekday(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly Monday with BYSETPOS=1 (should equal normal weekly)'
        );
    }

    /**
     * Test weekly BYSETPOS starting from different weekdays.
     * Week boundaries might affect which occurrences are selected.
     */
    public function testWeeklyBySetPosFromSunday(): void
    {
        $start = new DateTimeImmutable('2025-01-05 10:00:00'); // Sunday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=SU,TU,TH;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS starting from Sunday'
        );
    }

    /**
     * Test weekly BYSETPOS starting from Monday.
     * Different start days may reveal boundary issues.
     */
    public function testWeeklyBySetPosFromMonday(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS starting from Monday (week start)'
        );
    }

    /**
     * Test weekly BYSETPOS starting from Saturday.
     * End of week start might reveal different behavior.
     */
    public function testWeeklyBySetPosFromSaturday(): void
    {
        $start = new DateTimeImmutable('2025-01-04 10:00:00'); // Saturday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=SA,MO,WE;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS starting from Saturday (week end)'
        );
    }

    /**
     * Test weekly BYSETPOS with consecutive weekdays.
     * This tests ordering within consecutive days.
     */
    public function testWeeklyBySetPosConsecutiveDays(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,TU,WE;BYSETPOS=2;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS with consecutive weekdays'
        );
    }

    /**
     * Test weekly BYSETPOS with all weekdays.
     * This should select first/last day of each week.
     */
    public function testWeeklyBySetPosAllWeekdays(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR,SA,SU;BYSETPOS=1;COUNT=3',
            $start,
            3,
            'Weekly BYSETPOS with all weekdays (should select Monday each week)'
        );
    }

    /**
     * Test weekly BYSETPOS with interval > 1.
     * This tests behavior when combining BYSETPOS with weekly intervals.
     */
    public function testWeeklyBySetPosWithInterval(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00'); // Wednesday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=3',
            $start,
            3,
            'Bi-weekly BYSETPOS (every 2 weeks)'
        );
    }

    /**
     * Test weekly BYSETPOS boundary at month transition.
     * Week boundaries spanning months might reveal issues.
     */
    public function testWeeklyBySetPosMonthBoundary(): void
    {
        $start = new DateTimeImmutable('2025-01-27 10:00:00'); // Monday (last week of January)
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=-1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS across month boundary'
        );
    }

    /**
     * Test weekly BYSETPOS boundary at year transition.
     * Week boundaries spanning years might reveal issues.
     */
    public function testWeeklyBySetPosYearBoundary(): void
    {
        $start = new DateTimeImmutable('2024-12-30 10:00:00'); // Monday (last week of 2024)
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS across year boundary'
        );
    }

    /**
     * Test weekly BYSETPOS with multiple positions.
     * This tests selecting multiple positions within each week.
     */
    public function testWeeklyBySetPosMultiplePositions(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,3,5;COUNT=9',
            $start,
            9,
            'Weekly BYSETPOS with multiple positions (1st, 3rd, 5th of each week)'
        );
    }

    /**
     * Test weekly BYSETPOS with mixed positive and negative positions.
     * This tests combining first and last position selection.
     */
    public function testWeeklyBySetPosMixedPositions(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1,-1;COUNT=6',
            $start,
            6,
            'Weekly BYSETPOS with first and last positions'
        );
    }

    /**
     * Test weekly BYSETPOS when start date doesn't match BYDAY.
     * This tests the behavior when start date is not in BYDAY list.
     */
    public function testWeeklyBySetPosStartNotInByDay(): void
    {
        $start = new DateTimeImmutable('2025-01-07 10:00:00'); // Tuesday (not in BYDAY)
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=4',
            $start,
            4,
            'Weekly BYSETPOS when start date not in BYDAY'
        );
    }

    /**
     * Test weekly BYSETPOS edge case: position beyond available occurrences.
     * This tests what happens when asking for 4th occurrence but only 3 available.
     */
    public function testWeeklyBySetPosPositionBeyondAvailable(): void
    {
        $start = new DateTimeImmutable('2025-01-06 10:00:00'); // Monday
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE;BYSETPOS=3;COUNT=3',
            $start,
            3,
            'Weekly BYSETPOS position beyond available occurrences'
        );
    }
}
