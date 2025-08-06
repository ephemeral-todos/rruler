<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByMonthDayIntegrationTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideByMonthDayScenarios')]
    public function testByMonthDayEndToEndWorkflows(
        string $rruleString,
        string $startDate,
        array $expectedOccurrences,
        array $validCandidates,
        array $invalidCandidates,
    ): void {
        // Parse RRULE
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        // Generate occurrences
        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);
        $results = iterator_to_array($occurrences);

        // Verify expected occurrences
        $this->assertCount(count($expectedOccurrences), $results);
        foreach ($expectedOccurrences as $index => $expectedDate) {
            $this->assertEquals(
                new DateTimeImmutable($expectedDate),
                $results[$index],
                "Occurrence {$index} should match expected date for RRULE: {$rruleString}"
            );
        }

        // Validate each generated occurrence using validator
        foreach ($results as $occurrence) {
            $this->assertTrue(
                $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence),
                'Generated occurrence should be valid: '.$occurrence->format('Y-m-d')
            );
        }

        // Test valid candidates
        foreach ($validCandidates as $candidateDate) {
            $candidate = new DateTimeImmutable($candidateDate);
            $this->assertTrue(
                $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate),
                "Candidate should be valid: {$candidateDate} for RRULE: {$rruleString}"
            );
        }

        // Test invalid candidates
        foreach ($invalidCandidates as $candidateDate) {
            $candidate = new DateTimeImmutable($candidateDate);
            $this->assertFalse(
                $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate),
                "Candidate should be invalid: {$candidateDate} for RRULE: {$rruleString}"
            );
        }
    }

    public function testByMonthDayWithDateRangeFiltering(): void
    {
        // Monthly on 15th and last day, from Jan to June
        $rrule = $this->testRruler->parse('FREQ=MONTHLY;BYMONTHDAY=15,-1');
        $start = new DateTimeImmutable('2025-01-01');
        $rangeStart = new DateTimeImmutable('2025-02-01');
        $rangeEnd = new DateTimeImmutable('2025-04-30');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $results = iterator_to_array($occurrences);

        $expected = [
            '2025-02-15', // Feb 15th
            '2025-02-28', // Feb last day (28th)
            '2025-03-15', // Mar 15th
            '2025-03-31', // Mar last day (31st)
            '2025-04-15', // Apr 15th
            '2025-04-30', // Apr last day (30th)
        ];

        $this->assertCount(count($expected), $results);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }

        // Verify all results are within range
        foreach ($results as $occurrence) {
            $this->assertGreaterThanOrEqual($rangeStart, $occurrence);
            $this->assertLessThanOrEqual($rangeEnd, $occurrence);
        }
    }

    public function testByMonthDayLeapYearHandling(): void
    {
        // Test February 29th in leap year vs non-leap year
        $rrule = $this->testRruler->parse('FREQ=MONTHLY;BYMONTHDAY=29;COUNT=13');

        // Start in December 2023 (non-leap) and go through 2024 (leap)
        $start = new DateTimeImmutable('2023-12-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        // Expected: Skip Feb 2024 would be skipped if it was non-leap, but 2024 is leap
        $expected = [
            '2023-12-29', // Dec 2023
            '2024-01-29', // Jan 2024
            '2024-02-29', // Feb 2024 (leap year - included!)
            '2024-03-29', // Mar 2024
            '2024-04-29', // Apr 2024
            '2024-05-29', // May 2024
            '2024-06-29', // Jun 2024
            '2024-07-29', // Jul 2024
            '2024-08-29', // Aug 2024
            '2024-09-29', // Sep 2024
            '2024-10-29', // Oct 2024
            '2024-11-29', // Nov 2024
            '2024-12-29', // Dec 2024
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByMonthDayInvalidDateSkipping(): void
    {
        // Test 31st of month - should skip months with fewer than 31 days
        $rrule = $this->testRruler->parse('FREQ=MONTHLY;BYMONTHDAY=31;COUNT=7');
        $start = new DateTimeImmutable('2025-01-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        // Expected: Jan, Mar, May, Jul, Aug, Oct, Dec (skip Feb, Apr, Jun, Sep, Nov)
        $expected = [
            '2025-01-31', // Jan (31 days)
            '2025-03-31', // Mar (31 days) - skips Feb (28 days)
            '2025-05-31', // May (31 days) - skips Apr (30 days)
            '2025-07-31', // Jul (31 days) - skips Jun (30 days)
            '2025-08-31', // Aug (31 days)
            '2025-10-31', // Oct (31 days) - skips Sep (30 days)
            '2025-12-31', // Dec (31 days) - skips Nov (30 days)
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByMonthDayMultipleValuesOrdering(): void
    {
        // Test multiple BYMONTHDAY values in correct chronological order
        $rrule = $this->testRruler->parse('FREQ=MONTHLY;BYMONTHDAY=1,15,-1;COUNT=9');
        $start = new DateTimeImmutable('2025-01-10'); // Start after 1st but before 15th
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-01-15', // Jan 15th (next occurrence after start)
            '2025-01-31', // Jan 31st (last day)
            '2025-02-01', // Feb 1st
            '2025-02-15', // Feb 15th
            '2025-02-28', // Feb 28th (last day)
            '2025-03-01', // Mar 1st
            '2025-03-15', // Mar 15th
            '2025-03-31', // Mar 31st (last day)
            '2025-04-01', // Apr 1st
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testYearlyByMonthDayConsistency(): void
    {
        // Test yearly frequency applies BYMONTHDAY to same month each year
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYMONTHDAY=15;COUNT=3');
        $start = new DateTimeImmutable('2025-01-10'); // Start in January
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-01-15', // Jan 15th, 2025
            '2026-01-15', // Jan 15th, 2026 (same month each year)
            '2027-01-15', // Jan 15th, 2027 (same month each year)
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public static function provideByMonthDayScenarios(): array
    {
        return [
            'monthly single positive day' => [
                'FREQ=MONTHLY;BYMONTHDAY=15;COUNT=3',
                '2025-01-10',
                ['2025-01-15', '2025-02-15', '2025-03-15'],
                ['2025-01-15', '2025-02-15', '2025-03-15'], // Only within COUNT limit
                ['2025-01-14', '2025-01-16', '2025-02-14', '2025-02-16', '2025-04-15'], // 2025-04-15 is beyond COUNT
            ],

            'monthly single negative day' => [
                'FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=3',
                '2025-01-10',
                ['2025-01-31', '2025-02-28', '2025-03-31'],
                ['2025-01-31', '2025-02-28', '2025-03-31'], // Only within COUNT limit
                ['2025-01-30', '2025-02-27', '2025-03-30', '2025-04-30'], // 2025-04-30 is beyond COUNT
            ],

            'monthly multiple days' => [
                'FREQ=MONTHLY;BYMONTHDAY=1,15,-1;COUNT=6',
                '2025-01-10',
                ['2025-01-15', '2025-01-31', '2025-02-01', '2025-02-15', '2025-02-28', '2025-03-01'],
                ['2025-01-15', '2025-01-31', '2025-02-01', '2025-02-15', '2025-02-28', '2025-03-01'], // Only within COUNT limit
                ['2025-01-01', '2025-01-02', '2025-01-14', '2025-01-30', '2025-02-02', '2025-02-14', '2025-02-27'], // 2025-01-01 is before start
            ],

            'monthly with interval' => [
                'FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=15;COUNT=3',
                '2025-01-10',
                ['2025-01-15', '2025-03-15', '2025-05-15'],
                ['2025-01-15', '2025-03-15', '2025-05-15'], // Only within COUNT limit
                ['2025-02-15', '2025-04-15', '2025-06-15', '2025-07-15'], // 2025-07-15 is beyond COUNT, others wrong interval
            ],

            'monthly with count limit' => [
                'FREQ=MONTHLY;BYMONTHDAY=1,15,-1;COUNT=4',
                '2025-01-10',
                ['2025-01-15', '2025-01-31', '2025-02-01', '2025-02-15'],
                ['2025-01-15', '2025-01-31', '2025-02-01', '2025-02-15'],
                ['2025-02-28', '2025-03-01'], // These would be next but COUNT=4 limits
            ],

            'monthly with until limit' => [
                'FREQ=MONTHLY;BYMONTHDAY=15;UNTIL=20250315T235959Z',
                '2025-01-10',
                ['2025-01-15', '2025-02-15', '2025-03-15'],
                ['2025-01-15', '2025-02-15', '2025-03-15'],
                ['2025-04-15', '2025-05-15'], // Beyond UNTIL date
            ],

            'yearly same month pattern' => [
                'FREQ=YEARLY;BYMONTHDAY=15;COUNT=3',
                '2025-01-10',
                ['2025-01-15', '2026-01-15', '2027-01-15'],
                ['2025-01-15', '2026-01-15', '2027-01-15'],
                ['2025-02-15', '2025-03-15', '2026-02-15'], // Different months
            ],

            'yearly with negative day across leap years' => [
                'FREQ=YEARLY;BYMONTHDAY=-1;COUNT=3',
                '2023-02-10', // Start in February
                ['2023-02-28', '2024-02-29', '2025-02-28'], // 2024 is leap year
                ['2023-02-28', '2024-02-29', '2025-02-28'],
                ['2023-02-27', '2024-02-28', '2025-02-27'], // Not last days
            ],
        ];
    }
}
