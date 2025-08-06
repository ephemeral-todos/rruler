<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByMonthIntegrationTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideByMonthScenarios')]
    public function testByMonthEndToEndWorkflows(
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

    public function testByMonthWithDateRangeFiltering(): void
    {
        // Quarterly (March, June, September, December)
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12');
        $start = new DateTimeImmutable('2025-01-01');
        $rangeStart = new DateTimeImmutable('2025-05-01');
        $rangeEnd = new DateTimeImmutable('2026-08-31');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $results = iterator_to_array($occurrences);

        $expected = [
            '2025-06-01', // June 2025
            '2025-09-01', // September 2025
            '2025-12-01', // December 2025
            '2026-03-01', // March 2026
            '2026-06-01', // June 2026
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

    public function testByMonthLeapYearHandling(): void
    {
        // Test February in leap year vs non-leap year
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=2;COUNT=4');

        // Start in 2023 (non-leap) and go through 2026
        $start = new DateTimeImmutable('2023-02-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2023-02-01', // Feb 2023 (non-leap)
            '2024-02-01', // Feb 2024 (leap year)
            '2025-02-01', // Feb 2025 (non-leap)
            '2026-02-01', // Feb 2026 (non-leap)
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByMonthMultipleValuesOrdering(): void
    {
        // Test multiple BYMONTH values in correct chronological order
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=1,4,7,10;COUNT=8');
        $start = new DateTimeImmutable('2025-03-15'); // Start after January but before April
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-04-15', // April 2025 (next occurrence after start)
            '2025-07-15', // July 2025
            '2025-10-15', // October 2025
            '2026-01-15', // January 2026
            '2026-04-15', // April 2026
            '2026-07-15', // July 2026
            '2026-10-15', // October 2026
            '2027-01-15', // January 2027
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByMonthWithInterval(): void
    {
        // Every 2 years in June
        $rrule = $this->testRruler->parse('FREQ=YEARLY;INTERVAL=2;BYMONTH=6;COUNT=4');
        $start = new DateTimeImmutable('2025-01-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-06-01', // June 2025
            '2027-06-01', // June 2027 (skip 2026)
            '2029-06-01', // June 2029 (skip 2028)
            '2031-06-01', // June 2031 (skip 2030)
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByMonthDTSTARTAlignment(): void
    {
        // Test that DTSTART month is preserved when BYMONTH includes it
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=6,9,12;COUNT=6');
        $start = new DateTimeImmutable('2025-09-15'); // Start in September (included in BYMONTH)
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-09-15', // September 2025 (DTSTART included)
            '2025-12-15', // December 2025
            '2026-06-15', // June 2026
            '2026-09-15', // September 2026
            '2026-12-15', // December 2026
            '2027-06-15', // June 2027
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public static function provideByMonthScenarios(): array
    {
        return [
            'yearly single month' => [
                'FREQ=YEARLY;BYMONTH=6;COUNT=3',
                '2025-01-15',
                ['2025-06-15', '2026-06-15', '2027-06-15'],
                ['2025-06-15', '2026-06-15', '2027-06-15'],
                ['2025-05-15', '2025-07-15', '2026-05-15', '2028-06-15'], // 2028-06-15 is beyond COUNT
            ],

            'yearly quarterly pattern' => [
                'FREQ=YEARLY;BYMONTH=3,6,9,12;COUNT=8',
                '2025-01-10',
                ['2025-03-10', '2025-06-10', '2025-09-10', '2025-12-10', '2026-03-10', '2026-06-10', '2026-09-10', '2026-12-10'],
                ['2025-03-10', '2025-06-10', '2025-09-10', '2025-12-10', '2026-03-10', '2026-06-10', '2026-09-10', '2026-12-10'],
                ['2025-01-10', '2025-02-10', '2025-04-10', '2025-05-10', '2025-07-10', '2025-08-10', '2025-10-10', '2025-11-10'], // Wrong months
            ],

            'yearly with interval' => [
                'FREQ=YEARLY;INTERVAL=2;BYMONTH=6;COUNT=3',
                '2025-01-15',
                ['2025-06-15', '2027-06-15', '2029-06-15'],
                ['2025-06-15', '2027-06-15', '2029-06-15'],
                ['2026-06-15', '2028-06-15', '2030-06-15', '2031-06-15'], // 2031-06-15 is beyond COUNT, others wrong interval
            ],

            'yearly with count limit' => [
                'FREQ=YEARLY;BYMONTH=3,6,9,12;COUNT=5',
                '2025-01-10',
                ['2025-03-10', '2025-06-10', '2025-09-10', '2025-12-10', '2026-03-10'],
                ['2025-03-10', '2025-06-10', '2025-09-10', '2025-12-10', '2026-03-10'],
                ['2026-06-10', '2026-09-10'], // These would be next but COUNT=5 limits
            ],

            'yearly with until limit' => [
                'FREQ=YEARLY;BYMONTH=6;UNTIL=20270531T235959Z',
                '2025-01-10',
                ['2025-06-10', '2026-06-10'],
                ['2025-06-10', '2026-06-10'],
                ['2027-06-10', '2028-06-10'], // Beyond UNTIL date
            ],

            'yearly starting in specified month' => [
                'FREQ=YEARLY;BYMONTH=9;COUNT=3',
                '2025-09-15', // Start in the specified month
                ['2025-09-15', '2026-09-15', '2027-09-15'],
                ['2025-09-15', '2026-09-15', '2027-09-15'],
                ['2025-08-15', '2025-10-15', '2026-08-15'], // Different months
            ],

            'yearly all months pattern' => [
                'FREQ=YEARLY;BYMONTH=1,2,3,4,5,6,7,8,9,10,11,12;COUNT=13',
                '2025-01-15',
                [
                    '2025-01-15', '2025-02-15', '2025-03-15', '2025-04-15', '2025-05-15', '2025-06-15',
                    '2025-07-15', '2025-08-15', '2025-09-15', '2025-10-15', '2025-11-15', '2025-12-15',
                    '2026-01-15',
                ],
                [
                    '2025-01-15', '2025-02-15', '2025-03-15', '2025-04-15', '2025-05-15', '2025-06-15',
                    '2025-07-15', '2025-08-15', '2025-09-15', '2025-10-15', '2025-11-15', '2025-12-15',
                    '2026-01-15',
                ],
                ['2026-02-15'], // Beyond COUNT limit
            ],
        ];
    }
}
