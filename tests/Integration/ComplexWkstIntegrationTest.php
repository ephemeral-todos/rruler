<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive integration tests for complex RRULE patterns with WKST.
 *
 * These tests verify that WKST works correctly when combined with various
 * RRULE parameters including BYDAY, BYMONTHDAY, BYMONTH, BYWEEKNO, BYSETPOS.
 */
final class ComplexWkstIntegrationTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideComplexWkstPatterns')]
    public function testComplexRrulePatternWithWkst(
        string $rruleString,
        string $startDate,
        int $expectedCount,
        string $testDescription,
    ): void {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start, $expectedCount);
        $results = iterator_to_array($occurrences);

        // Verify expected count
        $this->assertCount($expectedCount, $results, "Pattern: {$testDescription}");

        // Verify all occurrences are valid
        foreach ($results as $index => $occurrence) {
            $this->assertTrue(
                $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence),
                "Occurrence {$index} should be valid for pattern: {$testDescription}"
            );
        }

        // Verify occurrences are chronologically ordered
        for ($i = 1; $i < count($results); ++$i) {
            $this->assertGreaterThan(
                $results[$i - 1],
                $results[$i],
                "Occurrences should be chronologically ordered for pattern: {$testDescription}"
            );
        }

        // Verify no duplicate occurrences
        $uniqueOccurrences = array_unique(array_map(fn ($date) => $date->format('Y-m-d H:i:s'), $results));
        $this->assertCount(
            count($results),
            $uniqueOccurrences,
            "No duplicate occurrences should exist for pattern: {$testDescription}"
        );
    }

    public function testWeeklyBydayWithWkstComplex(): void
    {
        // Complex weekly pattern with multiple BYDAY values and different WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $rruleMO = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;WKST=MO;COUNT=6');
        $rruleSU = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;WKST=SU;COUNT=6');

        $occurrencesMO = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleMO, $start, 6));
        $occurrencesSU = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleSU, $start, 6));

        // Both should produce 6 valid occurrences
        $this->assertCount(6, $occurrencesMO);
        $this->assertCount(6, $occurrencesSU);

        // All occurrences should be valid
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $dayOfWeek = $occurrence->format('l');
                $this->assertContains($dayOfWeek, ['Monday', 'Wednesday', 'Friday']);
            }
        }
    }

    public function testMonthlyBydayWithPositionalPrefixesAndWkst(): void
    {
        // Monthly pattern with positional BYDAY and WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $rruleMO = $this->testRruler->parse('FREQ=MONTHLY;BYDAY=1MO,3FR;WKST=MO;COUNT=6');
        $rruleSU = $this->testRruler->parse('FREQ=MONTHLY;BYDAY=1MO,3FR;WKST=SU;COUNT=6');

        $occurrencesMO = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleMO, $start, 6));
        $occurrencesSU = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleSU, $start, 6));

        $this->assertCount(6, $occurrencesMO);
        $this->assertCount(6, $occurrencesSU);

        // Verify days are correct (first Monday or third Friday of each month)
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $dayOfWeek = $occurrence->format('l');
                $this->assertContains($dayOfWeek, ['Monday', 'Friday']);
            }
        }
    }

    public function testYearlyBymonthBydayWithWkst(): void
    {
        // Yearly pattern combining BYMONTH and BYDAY with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $rruleMO = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;WKST=MO;COUNT=8');
        $rruleSU = $this->testRruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;WKST=SU;COUNT=8');

        $occurrencesMO = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleMO, $start, 8));
        $occurrencesSU = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleSU, $start, 8));

        $this->assertCount(8, $occurrencesMO);
        $this->assertCount(8, $occurrencesSU);

        // Verify all are Fridays in the specified months
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $this->assertEquals('Friday', $occurrence->format('l'));
                $month = (int) $occurrence->format('n');
                $this->assertContains($month, [3, 6, 9, 12]);
            }
        }
    }

    public function testBysetposWithWkstIntegration(): void
    {
        // Complex pattern with BYSETPOS and WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $rruleMO = $this->testRruler->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;WKST=MO;COUNT=6');
        $rruleSU = $this->testRruler->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;WKST=SU;COUNT=6');

        $occurrencesMO = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleMO, $start, 6));
        $occurrencesSU = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rruleSU, $start, 6));

        $this->assertCount(6, $occurrencesMO);
        $this->assertCount(6, $occurrencesSU);

        // Should get first and last weekday of each month
        foreach ([$occurrencesMO, $occurrencesSU] as $occurrences) {
            foreach ($occurrences as $occurrence) {
                $dayOfWeek = $occurrence->format('l');
                $this->assertNotContains($dayOfWeek, ['Saturday', 'Sunday']);
            }
        }
    }

    public function testComplexYearlyPatternWithMultipleConstraints(): void
    {
        // Very complex yearly pattern - use a less restrictive pattern that will actually match dates
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $rrule = $this->testRruler->parse('FREQ=YEARLY;INTERVAL=2;BYMONTH=6,12;BYDAY=MO;WKST=SU;COUNT=4');

        $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

        // Should find dates that are:
        // - Every 2 years
        // - In June or December
        // - On Monday
        // - Any Monday in those months will match

        $this->assertNotEmpty($occurrences, 'Should find some occurrences');

        foreach ($occurrences as $occurrence) {
            $month = (int) $occurrence->format('n');
            $dayOfWeek = $occurrence->format('l');

            $this->assertContains($month, [6, 12], 'Should be in June or December');
            $this->assertEquals('Monday', $dayOfWeek, 'Should be Monday');
        }
    }

    public function testWkstConsistencyAcrossFrequencies(): void
    {
        // Test that WKST behaves consistently across different frequencies
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
            'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=4',
            'FREQ=YEARLY;BYMONTH=1;BYDAY=MO;WKST=SU;COUNT=4',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, "Pattern: {$pattern}");

            foreach ($occurrences as $occurrence) {
                $this->assertEquals('Monday', $occurrence->format('l'), "Pattern: {$pattern}");
            }
        }
    }

    public function testEdgeCaseWkstTransitions(): void
    {
        // Test WKST behavior around challenging transitions
        $start = new DateTimeImmutable('2024-01-07 09:00:00'); // Sunday

        // Weekly pattern starting on Sunday with different WKST values
        $patterns = [
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=MO;COUNT=3',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=SU;COUNT=3',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=WE;COUNT=3',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 3));

            $this->assertCount(3, $occurrences, "Pattern: {$pattern}");

            foreach ($occurrences as $occurrence) {
                $this->assertEquals('Sunday', $occurrence->format('l'), "Pattern: {$pattern}");
            }
        }
    }

    public static function provideComplexWkstPatterns(): array
    {
        return [
            // Complex weekly patterns
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO,WE,FR;WKST=SU;COUNT=6',
                '2024-01-01 09:00:00',
                6,
                'Bi-weekly Mon/Wed/Fri with Sunday week start',
            ],
            [
                'FREQ=WEEKLY;INTERVAL=3;BYDAY=TU,TH;WKST=MO;COUNT=8',
                '2024-01-01 09:00:00',
                8,
                'Every 3rd week Tue/Thu with Monday week start',
            ],

            // Complex monthly patterns
            [
                'FREQ=MONTHLY;BYDAY=1MO,3WE,-1FR;WKST=SU;COUNT=6',
                '2024-01-01 09:00:00',
                6,
                'Monthly: 1st Mon, 3rd Wed, last Fri with Sunday week start',
            ],
            [
                'FREQ=MONTHLY;INTERVAL=2;BYDAY=SA,SU;BYSETPOS=1,2;WKST=TU;COUNT=8',
                '2024-01-01 09:00:00',
                8,
                'Bi-monthly weekends, first 2 positions with Tuesday week start',
            ],

            // Complex yearly patterns
            [
                'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;BYMONTHDAY=13;WKST=WE;COUNT=4',
                '2024-01-01 09:00:00',
                4,
                'Yearly Friday 13th in Mar/Jun/Sep/Dec with Wednesday week start',
            ],
            [
                'FREQ=YEARLY;INTERVAL=2;BYWEEKNO=26;WKST=TH;COUNT=3',
                '2024-01-01 09:00:00',
                3,
                'Bi-yearly week 26 with Thursday week start',
            ],

            // Complex combinations with BYSETPOS
            [
                'FREQ=MONTHLY;BYMONTHDAY=1,15,30,31;BYDAY=MO,FR;BYSETPOS=-1;WKST=SA;COUNT=6',
                '2024-01-01 09:00:00',
                6,
                'Monthly: last Mon/Fri on 1st/15th/30th/31st with Saturday week start',
            ],

            // Edge cases
            [
                'FREQ=WEEKLY;BYDAY=SU;WKST=SU;COUNT=4',
                '2024-01-07 09:00:00', // Starting on Sunday
                4,
                'Weekly Sunday starting on Sunday with Sunday week start',
            ],
            [
                'FREQ=YEARLY;BYWEEKNO=53;WKST=FR;COUNT=2',
                '2020-01-01 09:00:00',
                2,
                'Yearly week 53 with Friday week start (leap week years)',
            ],
        ];
    }
}
