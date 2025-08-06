<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByWeekNoIntegrationTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideByWeekNoScenarios')]
    public function testByWeekNoEndToEndWorkflows(
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

    public function testByWeekNoWithDateRangeFiltering(): void
    {
        // Quarterly pattern (weeks 13, 26, 39, 52)
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=13,26,39,52');
        $start = new DateTimeImmutable('2025-01-01');
        $rangeStart = new DateTimeImmutable('2025-05-01');
        $rangeEnd = new DateTimeImmutable('2026-08-31');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $results = iterator_to_array($occurrences);

        $expected = [
            '2025-06-25', // Week 26 of 2025 (Wednesday - same day as start)
            '2025-09-24', // Week 39 of 2025 (Wednesday)
            '2025-12-24', // Week 52 of 2025 (Wednesday)
            '2026-03-25', // Week 13 of 2026 (Wednesday)
            '2026-06-24', // Week 26 of 2026 (Wednesday)
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

    public function testByWeekNoLeapWeekHandling(): void
    {
        // Test week 53 in leap week years
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=53;COUNT=3');

        // Start in 2020 (a year with week 53)
        $start = new DateTimeImmutable('2020-01-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2020-12-30', // Week 53 of 2020 (Wednesday - same day as start)
            '2026-12-30', // Week 53 of 2026 (next year with week 53)
            '2032-12-29', // Week 53 of 2032 (next year with week 53)
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoMultipleValuesOrdering(): void
    {
        // Test multiple BYWEEKNO values in correct chronological order
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=1,13,26,39;COUNT=8');
        $start = new DateTimeImmutable('2025-02-15'); // Start after week 1 but before week 13
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-03-29', // Week 13 of 2025 (next occurrence after start) - Saturday
            '2025-06-28', // Week 26 of 2025 - Saturday
            '2025-09-27', // Week 39 of 2025 - Saturday
            '2026-01-03', // Week 1 of 2026 - Saturday
            '2026-03-28', // Week 13 of 2026 - Saturday
            '2026-06-27', // Week 26 of 2026 - Saturday
            '2026-09-26', // Week 39 of 2026 - Saturday
            '2027-01-09', // Week 1 of 2027 - Saturday
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoWithInterval(): void
    {
        // Every 2 years in week 26
        $rrule = $this->testRruler->parse('FREQ=YEARLY;INTERVAL=2;BYWEEKNO=26;COUNT=4');
        $start = new DateTimeImmutable('2025-01-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-06-25', // Week 26 of 2025 - Wednesday
            '2027-06-30', // Week 26 of 2027 (skip 2026) - Wednesday
            '2029-06-27', // Week 26 of 2029 (skip 2028) - Wednesday
            '2031-06-25', // Week 26 of 2031 (skip 2030) - Wednesday
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoDTSTARTAlignment(): void
    {
        // Test that DTSTART week is preserved when BYWEEKNO includes it
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=10,26,39;COUNT=6');
        $start = new DateTimeImmutable('2025-03-10'); // Week 11 (not in BYWEEKNO)
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-06-23', // Week 26 of 2025 (next valid week after start) - Monday
            '2025-09-22', // Week 39 of 2025 - Monday
            '2026-03-02', // Week 10 of 2026 - Monday
            '2026-06-22', // Week 26 of 2026 - Monday
            '2026-09-21', // Week 39 of 2026 - Monday
            '2027-03-08', // Week 10 of 2027 - Monday
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoErrorHandling(): void
    {
        // Test that invalid week values are properly handled during parsing
        $this->expectException(\EphemeralTodos\Rruler\Exception\ValidationException::class);
        $this->expectExceptionMessage('Week number cannot be zero');

        $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=0');
    }

    public function testByWeekNoWithUntilTermination(): void
    {
        // Test BYWEEKNO with UNTIL termination condition
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=26;UNTIL=20260620T235959Z');
        $start = new DateTimeImmutable('2025-01-01');
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        // Should only get 2025 occurrence, not 2026 (UNTIL is before week 26 of 2026)
        $expected = ['2025-06-25']; // Week 26 2025, Wednesday

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoBiAnnualPattern(): void
    {
        // Test bi-annual pattern (every 6 months) - simplified version
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=26;COUNT=3');
        $start = new DateTimeImmutable('2025-01-01'); // Wednesday
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-06-25', // Week 26 of 2025 - Wednesday
            '2026-06-24', // Week 26 of 2026 - Wednesday
            '2027-06-30', // Week 26 of 2027 - Wednesday
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public function testByWeekNoSimpleMultipleWeeks(): void
    {
        // Test simple multiple weeks in same year
        $rrule = $this->testRruler->parse('FREQ=YEARLY;BYWEEKNO=10,20,30;COUNT=6');
        $start = new DateTimeImmutable('2025-01-01'); // Wednesday
        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start));

        $expected = [
            '2025-03-05', // Week 10 of 2025 - Wednesday
            '2025-05-14', // Week 20 of 2025 - Wednesday
            '2025-07-23', // Week 30 of 2025 - Wednesday
            '2026-03-04', // Week 10 of 2026 - Wednesday
            '2026-05-13', // Week 20 of 2026 - Wednesday
            '2026-07-22', // Week 30 of 2026 - Wednesday
        ];

        $this->assertCount(count($expected), $occurrences);
        foreach ($expected as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $occurrences[$index]);
        }
    }

    public static function provideByWeekNoScenarios(): array
    {
        return [
            'yearly single week' => [
                'FREQ=YEARLY;BYWEEKNO=26;COUNT=3',
                '2025-01-15', // Wednesday
                ['2025-06-25', '2026-06-24', '2027-06-30'], // Week 26 Wednesday each year
                ['2025-06-25', '2026-06-24', '2027-06-30'],
                ['2025-06-23', '2025-06-30', '2026-06-22', '2028-06-26'], // Wrong days of week or beyond COUNT
            ],

            'yearly quarterly pattern' => [
                'FREQ=YEARLY;BYWEEKNO=13,26,39,52;COUNT=8',
                '2025-01-10', // Friday
                ['2025-03-28', '2025-06-27', '2025-09-26', '2025-12-26', '2026-03-27', '2026-06-26', '2026-09-25', '2026-12-25'], // Fridays in specified weeks
                ['2025-03-28', '2025-06-27', '2025-09-26', '2025-12-26', '2026-03-27', '2026-06-26', '2026-09-25', '2026-12-25'],
                ['2025-01-10', '2025-02-10', '2025-04-10', '2025-05-10', '2025-07-10', '2025-08-10'], // Wrong weeks
            ],

            'yearly with interval' => [
                'FREQ=YEARLY;INTERVAL=2;BYWEEKNO=26;COUNT=3',
                '2025-01-15', // Wednesday
                ['2025-06-25', '2027-06-30', '2029-06-27'], // Week 26 Wednesday every 2 years
                ['2025-06-25', '2027-06-30', '2029-06-27'],
                ['2026-06-24', '2028-06-26', '2030-06-25'], // Wrong interval years
            ],

            'yearly starting in specified week' => [
                'FREQ=YEARLY;BYWEEKNO=10;COUNT=3',
                '2025-03-03', // Monday in week 10
                ['2025-03-03', '2026-03-02', '2027-03-08'], // Week 10 Monday each year
                ['2025-03-03', '2026-03-02', '2027-03-08'],
                ['2025-02-24', '2025-03-10', '2026-02-23'], // Different weeks
            ],

            'yearly leap week pattern' => [
                'FREQ=YEARLY;BYWEEKNO=53;COUNT=2',
                '2020-01-01', // Wednesday in a year with week 53
                ['2020-12-30', '2026-12-30'], // Week 53 Wednesday in years that have it
                ['2020-12-30', '2026-12-30'],
                ['2021-12-29', '2025-12-29'], // Years without week 53
            ],
        ];
    }
}
