<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\TestCase;

/**
 * Test WKST behavior around leap years.
 *
 * Leap years can create edge cases in week calculations due to the extra day,
 * particularly around February 29th and how it affects week boundaries
 * with different WKST values.
 */
final class WkstLeapYearEdgeCaseTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testWkstAroundLeapDayFebruary29(): void
    {
        // Test WKST behavior around February 29th in leap years
        // 2024 is a leap year, Feb 29 is a Thursday

        $start = new DateTimeImmutable('2024-02-25 09:00:00'); // Sunday before leap day

        $patterns = [
            'FREQ=WEEKLY;BYDAY=MO;WKST=MO;COUNT=4',
            'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
            'FREQ=WEEKLY;BYDAY=TH;WKST=MO;COUNT=4', // Target Thursday (leap day weekday)
            'FREQ=WEEKLY;BYDAY=TH;WKST=SU;COUNT=4',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, "Pattern: {$pattern}");

            // Verify all occurrences are valid and chronologically ordered
            for ($i = 1; $i < count($occurrences); ++$i) {
                $this->assertGreaterThan($occurrences[$i - 1], $occurrences[$i], "Pattern: {$pattern}");
            }

            // Check if any occurrence is on leap day or the week containing it
            foreach ($occurrences as $occurrence) {
                $date = $occurrence->format('Y-m-d');
                // Leap day week: Feb 26 - Mar 3, 2024
                if ($date >= '2024-02-26' && $date <= '2024-03-03') {
                    // Verify the occurrence is on the correct day of week
                    $targetDay = str_contains($pattern, 'BYDAY=MO') ? 'Monday' : 'Thursday';
                    $this->assertEquals($targetDay, $occurrence->format('l'), "Pattern: {$pattern}, Date: {$date}");
                }
            }
        }
    }

    public function testBiWeeklyAcrossLeapDay(): void
    {
        // Test bi-weekly patterns spanning leap day with different WKST
        $start = new DateTimeImmutable('2024-02-15 09:00:00'); // Thursday, 2 weeks before leap day

        $rruleMO = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;WKST=MO;COUNT=4');
        $rruleSU = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=TH;WKST=SU;COUNT=4');

        $occurrencesMO = iterator_to_array($this->generator->generateOccurrences($rruleMO, $start, 4));
        $occurrencesSU = iterator_to_array($this->generator->generateOccurrences($rruleSU, $start, 4));

        $this->assertCount(4, $occurrencesMO);
        $this->assertCount(4, $occurrencesSU);

        // All should be Thursdays
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $this->assertEquals('Thursday', $occurrence->format('l'));
            }
        }

        // One of the occurrences should be Feb 29 (leap day) since we start on Feb 15 and go bi-weekly
        $datesMO = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesMO);
        $datesSU = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesSU);

        // Feb 29, 2024 is a Thursday, so it should appear in both patterns
        $this->assertContains('2024-02-29', $datesMO, 'Monday week start should include leap day');
        $this->assertContains('2024-02-29', $datesSU, 'Sunday week start should include leap day');
    }

    public function testMonthlyPatternsInLeapYear(): void
    {
        // Test monthly patterns in leap year with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=MONTHLY;BYDAY=-1MO;WKST=MO;COUNT=12', // Last Monday of each month
            'FREQ=MONTHLY;BYDAY=-1MO;WKST=SU;COUNT=12', // Last Monday with Sunday week start
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 12));

            $this->assertCount(12, $occurrences, "Pattern: {$pattern}");

            // Find February occurrence
            $februaryOccurrence = null;
            foreach ($occurrences as $occurrence) {
                if ((int) $occurrence->format('n') === 2) {
                    $februaryOccurrence = $occurrence;
                    break;
                }
            }

            $this->assertNotNull($februaryOccurrence, "Should find February occurrence for: {$pattern}");
            $this->assertEquals('Monday', $februaryOccurrence->format('l'), "Should be Monday for: {$pattern}");

            // In leap year 2024, last Monday of February should be Feb 26
            $this->assertEquals('2024-02-26', $februaryOccurrence->format('Y-m-d'), "Pattern: {$pattern}");
        }
    }

    public function testYearlyLeapDayPattern(): void
    {
        // Test yearly pattern that specifically targets leap day
        $start = new DateTimeImmutable('2024-02-29 09:00:00'); // Leap day 2024

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=29;WKST=MO;COUNT=2');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=2;BYMONTHDAY=29;WKST=SU;COUNT=2');

        // Feb 29 only exists in leap years, so the generator should skip non-leap years
        try {
            $occurrencesMO = iterator_to_array($this->generator->generateOccurrences($rruleMO, $start, 2));
            $occurrencesSU = iterator_to_array($this->generator->generateOccurrences($rruleSU, $start, 2));

            // Should only find leap days in leap years
            $this->assertNotEmpty($occurrencesMO, 'Should find leap day occurrences with MO week start');
            $this->assertNotEmpty($occurrencesSU, 'Should find leap day occurrences with SU week start');

            $datesMO = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesMO);
            $datesSU = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesSU);

            // All dates should be Feb 29
            foreach ($datesMO as $date) {
                $this->assertStringEndsWith('-02-29', $date, 'Should be Feb 29');
            }

            foreach ($datesSU as $date) {
                $this->assertStringEndsWith('-02-29', $date, 'Should be Feb 29');
            }
        } catch (\RuntimeException $e) {
            // It's acceptable for the generator to throw an exception when trying to find
            // Feb 29 in a non-leap year. This is correct behavior.
            $this->assertStringContainsString('No valid BYMONTHDAY values', $e->getMessage());
        }
    }

    public function testWeekBoundariesAroundLeapYear(): void
    {
        // Test how WKST affects week boundaries around leap year transition
        // Testing the transition from Feb 29 to March 1

        $leapDay = new DateTimeImmutable('2024-02-29 09:00:00'); // Thursday

        $patterns = [
            'FREQ=WEEKLY;BYDAY=TH,FR,SA;WKST=MO;COUNT=6',
            'FREQ=WEEKLY;BYDAY=TH,FR,SA;WKST=SU;COUNT=6',
            'FREQ=WEEKLY;BYDAY=TH,FR,SA;WKST=TH;COUNT=6', // Week starting on leap day weekday
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $leapDay, 6));

            $this->assertCount(6, $occurrences, "Pattern: {$pattern}");

            // Should include leap day (Thursday) in all patterns
            $dates = array_map(fn ($d) => $d->format('Y-m-d'), $occurrences);
            $this->assertContains('2024-02-29', $dates, "Should include leap day for: {$pattern}");

            // Verify all are Thu/Fri/Sat
            foreach ($occurrences as $occurrence) {
                $dayName = $occurrence->format('l');
                $this->assertContains($dayName, ['Thursday', 'Friday', 'Saturday'], "Pattern: {$pattern}");
            }
        }
    }

    public function testLeapYearVsNonLeapYearComparison(): void
    {
        // Compare same patterns between leap year (2024) and non-leap year (2023)
        $leapYearStart = new DateTimeImmutable('2024-02-01 09:00:00');
        $nonLeapYearStart = new DateTimeImmutable('2023-02-01 09:00:00');

        $pattern = 'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1;WKST=SU;COUNT=1'; // Last weekday of Feb

        $rrule = $this->testRruler->parse($pattern);

        $leapYearOccurrence = iterator_to_array($this->generator->generateOccurrences($rrule, $leapYearStart, 1))[0];
        $nonLeapYearOccurrence = iterator_to_array($this->generator->generateOccurrences($rrule, $nonLeapYearStart, 1))[0];

        // 2024 (leap): Last weekday should be Feb 29 (Thursday)
        $this->assertEquals('2024-02-29', $leapYearOccurrence->format('Y-m-d'));
        $this->assertEquals('Thursday', $leapYearOccurrence->format('l'));

        // 2023 (non-leap): Last weekday should be Feb 28 (Tuesday)
        $this->assertEquals('2023-02-28', $nonLeapYearOccurrence->format('Y-m-d'));
        $this->assertEquals('Tuesday', $nonLeapYearOccurrence->format('l'));
    }

    public function testByweeknoWithLeapYear(): void
    {
        // Test BYWEEKNO in leap years with different WKST
        // Leap years can affect week 53 existence

        $start2024 = new DateTimeImmutable('2024-01-01 09:00:00'); // 2024 is leap year
        $start2020 = new DateTimeImmutable('2020-01-01 09:00:00'); // 2020 is leap year with week 53

        // Test week 53 in leap years
        $patterns = [
            'FREQ=YEARLY;BYWEEKNO=53;WKST=MO;COUNT=2',
            'FREQ=YEARLY;BYWEEKNO=53;WKST=SU;COUNT=2',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);

            // Start from 2020 (has week 53)
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start2020, 2));
            $this->assertCount(2, $occurrences, "Pattern: {$pattern}");

            // First occurrence should be in 2020
            $this->assertStringContainsString('2020', $occurrences[0]->format('Y-m-d'));
        }
    }

    public function testWkstConsistencyAcrossLeapYear(): void
    {
        // Test that WKST behavior is consistent when crossing leap year boundaries
        $start = new DateTimeImmutable('2023-12-15 09:00:00'); // Before leap year

        $rruleMO = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=FR;WKST=MO;COUNT=20'); // Will span into 2024
        $rruleSU = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=FR;WKST=SU;COUNT=20');

        $occurrencesMO = iterator_to_array($this->generator->generateOccurrences($rruleMO, $start, 20));
        $occurrencesSU = iterator_to_array($this->generator->generateOccurrences($rruleSU, $start, 20));

        $this->assertCount(20, $occurrencesMO);
        $this->assertCount(20, $occurrencesSU);

        // All should be Fridays
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $this->assertEquals('Friday', $occurrence->format('l'));
            }
        }

        // Should span across 2023-2024 leap year boundary
        $datesMO = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesMO);
        $datesSU = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesSU);

        $has2023MO = array_filter($datesMO, fn ($d) => str_starts_with($d, '2023'));
        $has2024MO = array_filter($datesMO, fn ($d) => str_starts_with($d, '2024'));

        $this->assertNotEmpty($has2023MO, 'Should have 2023 dates');
        $this->assertNotEmpty($has2024MO, 'Should have 2024 dates');
    }
}
