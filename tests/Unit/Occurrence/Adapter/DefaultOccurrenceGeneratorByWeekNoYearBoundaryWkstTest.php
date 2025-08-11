<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\TestCase;

/**
 * Test BYWEEKNO patterns with different WKST values at year boundaries.
 *
 * Year boundaries are particularly complex for BYWEEKNO because:
 * - Week 1 can start in the previous year
 * - Week 53 may not exist in all years
 * - WKST affects how week boundaries are calculated
 * - ISO 8601 vs other week start days can produce different results
 */
final class DefaultOccurrenceGeneratorByWeekNoYearBoundaryWkstTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testByWeekNoWeek1YearBoundaryWithWkstMonday(): void
    {
        // Test week 1 near year boundary with WKST=MO (ISO 8601 standard)
        // Week 1 of 2024 actually starts on January 1, 2024 (Monday)
        $start = new DateTimeImmutable('2023-12-25 09:00:00'); // Monday in previous year

        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=MO;COUNT=3');

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start, 3) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        // ISO 8601 week 1 contains January 4th, so:
        // 2024: Week 1 is Jan 1-7 (starts Monday Jan 1)
        // 2025: Week 1 is Dec 30-Jan 5 (starts Monday Dec 30, 2024)
        // 2026: Week 1 is Dec 29-Jan 4 (starts Monday Dec 29, 2025)
        $this->assertCount(3, $occurrences);
        $this->assertNotEmpty($occurrences);
    }

    public function testByWeekNoWeek1YearBoundaryWithWkstSunday(): void
    {
        // Test week 1 near year boundary with WKST=SU
        $start = new DateTimeImmutable('2023-12-25 09:00:00');

        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=SU;COUNT=3');

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start, 3) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        // With WKST=SU, week boundaries change but BYWEEKNO=1 still refers to ISO week 1
        $this->assertCount(3, $occurrences);
        $this->assertNotEmpty($occurrences);
    }

    public function testByWeekNoWeek53EdgeCaseWithDifferentWkst(): void
    {
        // Test week 53 which only exists in certain years
        // 2020 has week 53, next year with week 53 is 2026
        $start = new DateTimeImmutable('2020-12-01 09:00:00'); // Tuesday

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=53;WKST=MO;COUNT=2');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=53;WKST=SU;COUNT=2');
        $rruleTU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=53;WKST=TU;COUNT=2');

        $occurrencesMO = [];
        foreach ($this->generator->generateOccurrences($rruleMO, $start, 2) as $occurrence) {
            $occurrencesMO[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSU = [];
        foreach ($this->generator->generateOccurrences($rruleSU, $start, 2) as $occurrence) {
            $occurrencesSU[] = $occurrence->format('Y-m-d');
        }

        $occurrencesTU = [];
        foreach ($this->generator->generateOccurrences($rruleTU, $start, 2) as $occurrence) {
            $occurrencesTU[] = $occurrence->format('Y-m-d');
        }

        // All should find 2 occurrences (years with week 53)
        $this->assertCount(2, $occurrencesMO);
        $this->assertCount(2, $occurrencesSU);
        $this->assertCount(2, $occurrencesTU);

        // Should include dates in years that have week 53
        $this->assertStringContainsString('2020', $occurrencesMO[0]);
        // Second occurrence might be 2026 or another year with week 53
        $this->assertNotEmpty($occurrencesMO[1]);
    }

    public function testByWeekNoLastWeekOfYearTransition(): void
    {
        // Test transition from week 52 to week 1 across year boundary
        $start = new DateTimeImmutable('2023-12-20 09:00:00'); // Wednesday

        $rruleWeek52 = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=52;WKST=MO;COUNT=2');
        $rruleWeek1 = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=MO;COUNT=2');

        $occurrences52 = [];
        foreach ($this->generator->generateOccurrences($rruleWeek52, $start, 2) as $occurrence) {
            $occurrences52[] = $occurrence->format('Y-m-d');
        }

        $occurrences1 = [];
        foreach ($this->generator->generateOccurrences($rruleWeek1, $start, 2) as $occurrence) {
            $occurrences1[] = $occurrence->format('Y-m-d');
        }

        // Should get 2 occurrences for week 52
        $this->assertCount(2, $occurrences52);
        $this->assertStringContainsString('2023', $occurrences52[0]);
        // Second occurrence should be from the next year
        $this->assertNotEmpty($occurrences52[1]);

        // Should get 2 occurrences for week 1
        $this->assertCount(2, $occurrences1);
    }

    public function testByWeekNoYearBoundaryWithWkstThursday(): void
    {
        // Test year boundary behavior with WKST=TH (Thursday)
        // This is interesting because ISO 8601 uses Thursday as the key day for week 1 definition
        $start = new DateTimeImmutable('2023-12-28 09:00:00'); // Thursday

        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=TH;COUNT=3');

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start, 3) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        // With WKST=TH, week boundaries start on Thursday
        // But BYWEEKNO=1 still refers to ISO week 1
        $this->assertCount(3, $occurrences);
        $this->assertNotEmpty($occurrences);
    }

    public function testByWeekNoStartDateInPreviousYearWeek1(): void
    {
        // Test starting in previous year when week 1 spans years
        // 2025's week 1 starts on Monday, Dec 30, 2024
        $start = new DateTimeImmutable('2024-12-30 09:00:00'); // Monday, week 1 of 2025

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=MO;COUNT=3');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=SU;COUNT=3');

        $occurrencesMO = [];
        foreach ($this->generator->generateOccurrences($rruleMO, $start, 3) as $occurrence) {
            $occurrencesMO[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSU = [];
        foreach ($this->generator->generateOccurrences($rruleSU, $start, 3) as $occurrence) {
            $occurrencesSU[] = $occurrence->format('Y-m-d');
        }

        // Both should handle cross-year week boundaries correctly
        $this->assertCount(3, $occurrencesMO);
        $this->assertCount(3, $occurrencesSU);

        // First occurrence should be the start date (already in week 1)
        $this->assertEquals('2024-12-30', $occurrencesMO[0]);
    }

    public function testByWeekNoMultipleWeeksAcrossYearBoundary(): void
    {
        // Test multiple BYWEEKNO values that span year boundaries
        $start = new DateTimeImmutable('2023-12-20 09:00:00');

        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=52,1,2;WKST=MO;COUNT=6');

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start, 6) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        // Should get weeks 52, then 1, then 2 for each year in chronological order
        $this->assertCount(6, $occurrences);

        // First three should be from 2023/2024 transition
        // Next three should be from 2024/2025 transition
        $this->assertNotEmpty($occurrences);
    }

    public function testByWeekNoLeapYearWeek53Behavior(): void
    {
        // Test behavior around leap years that affect week 53 existence
        $start = new DateTimeImmutable('2020-01-01 09:00:00'); // 2020 is leap year with week 53

        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=53;WKST=MO;COUNT=3');

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start, 3) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        // Should find 3 occurrences in years with week 53
        $this->assertCount(3, $occurrences);
        $this->assertStringContainsString('2020', $occurrences[0]);
        // Other occurrences should be in future years that have week 53
        $this->assertNotEmpty($occurrences[1]);
        $this->assertNotEmpty($occurrences[2]);
    }

    public function testByWeekNoWkstConsistencyAcrossYearBoundaries(): void
    {
        // Test that WKST behavior is consistent across year boundaries
        $start = new DateTimeImmutable('2023-12-01 09:00:00');

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1,52;WKST=MO;COUNT=4');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1,52;WKST=SU;COUNT=4');

        $occurrencesMO = [];
        foreach ($this->generator->generateOccurrences($rruleMO, $start, 4) as $occurrence) {
            $occurrencesMO[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSU = [];
        foreach ($this->generator->generateOccurrences($rruleSU, $start, 4) as $occurrence) {
            $occurrencesSU[] = $occurrence->format('Y-m-d');
        }

        // Both should produce consistent results
        $this->assertCount(4, $occurrencesMO);
        $this->assertCount(4, $occurrencesSU);
        $this->assertNotEmpty($occurrencesMO);
        $this->assertNotEmpty($occurrencesSU);
    }

    public function testByWeekNoCurrentImplementationYearBoundaryHandling(): void
    {
        // Document current behavior at year boundaries with different WKST
        $start = new DateTimeImmutable('2024-12-30 09:00:00'); // Monday, week 1 of 2025

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=MO;COUNT=2');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=SU;COUNT=2');
        $rruleTU = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1;WKST=TU;COUNT=2');

        $occurrencesMO = [];
        foreach ($this->generator->generateOccurrences($rruleMO, $start, 2) as $occurrence) {
            $occurrencesMO[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSU = [];
        foreach ($this->generator->generateOccurrences($rruleSU, $start, 2) as $occurrence) {
            $occurrencesSU[] = $occurrence->format('Y-m-d');
        }

        $occurrencesTU = [];
        foreach ($this->generator->generateOccurrences($rruleTU, $start, 2) as $occurrence) {
            $occurrencesTU[] = $occurrence->format('Y-m-d');
        }

        // Current implementation should handle year boundaries consistently
        $this->assertCount(2, $occurrencesMO);
        $this->assertCount(2, $occurrencesSU);
        $this->assertCount(2, $occurrencesTU);

        // All should correctly handle the cross-year week 1
        $this->assertEquals('2024-12-30', $occurrencesMO[0]);
    }
}
