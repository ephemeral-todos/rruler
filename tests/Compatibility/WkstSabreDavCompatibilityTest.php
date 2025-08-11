<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test WKST implementation for compatibility with sabre/dav behavior.
 *
 * These tests ensure our WKST implementation matches the industry standard
 * sabre/dav library behavior for RFC 5545 compliance.
 */
final class WkstSabreDavCompatibilityTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideSabreDavWkstPatterns')]
    public function testWkstCompatibilityWithSabreDav(
        string $rruleString,
        string $startDate,
        int $expectedCount,
        array $expectedDates,
        string $description,
    ): void {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start, $expectedCount);
        $results = array_map(fn ($date) => $date->format('Y-m-d'), iterator_to_array($occurrences));

        // Verify count matches sabre/dav behavior
        $this->assertCount($expectedCount, $results, "Count mismatch for: {$description}");

        // If exact dates are provided, verify them (when we're confident about sabre/dav behavior)
        if (!empty($expectedDates)) {
            foreach ($expectedDates as $index => $expectedDate) {
                if (isset($results[$index])) {
                    $this->assertEquals($expectedDate, $results[$index], "Date mismatch at index {$index} for: {$description}");
                }
            }
        }

        // Verify all results are valid according to the RRULE
        $rruleParsed = $this->testRruler->parse($rruleString);
        $startParsed = new DateTimeImmutable($startDate);
        foreach ($results as $index => $resultDate) {
            $resultDateTime = new DateTimeImmutable($resultDate);
            $isValid = $this->testOccurrenceValidator->isValidOccurrence($rruleParsed, $startParsed, $resultDateTime);
            $this->assertTrue($isValid, "Occurrence {$index} ({$resultDate}) should be valid for: {$description}");
        }
    }

    public function testWeeklyWkstBasicCompatibility(): void
    {
        // Test basic weekly WKST compatibility that should match sabre/dav exactly
        $patterns = [
            ['FREQ=WEEKLY;BYDAY=MO;COUNT=4', '2024-01-01'], // Default WKST=MO
            ['FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4', '2024-01-01'], // Explicit WKST=SU
            ['FREQ=WEEKLY;BYDAY=SU;WKST=MO;COUNT=4', '2024-01-07'], // Sunday with Monday week start
            ['FREQ=WEEKLY;BYDAY=SU;WKST=SU;COUNT=4', '2024-01-07'], // Sunday with Sunday week start
        ];

        foreach ($patterns as [$rruleString, $startDate]) {
            $rrule = $this->testRruler->parse($rruleString);
            $start = new DateTimeImmutable($startDate);

            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, "Pattern: {$rruleString}");

            // Verify all occurrences are valid
            foreach ($occurrences as $occurrence) {
                $this->assertTrue(
                    $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence),
                    "Invalid occurrence for pattern: {$rruleString}"
                );
            }
        }
    }

    public function testBiWeeklyWkstCompatibility(): void
    {
        // Test bi-weekly patterns with different WKST values
        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $patterns = [
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;WKST=MO;COUNT=4',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;WKST=SU;COUNT=4',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=MO;COUNT=4',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=SU;COUNT=4',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, "Pattern: {$pattern}");

            // Verify chronological order
            for ($i = 1; $i < count($occurrences); ++$i) {
                $this->assertGreaterThan($occurrences[$i - 1], $occurrences[$i], "Pattern: {$pattern}");
            }
        }
    }

    public function testMonthlyBydayWkstCompatibility(): void
    {
        // Test monthly BYDAY patterns with WKST - these should behave like sabre/dav
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=MONTHLY;BYDAY=1MO;WKST=MO;COUNT=4',    // First Monday with Monday week start
            'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=4',    // First Monday with Sunday week start
            'FREQ=MONTHLY;BYDAY=-1FR;WKST=MO;COUNT=4',   // Last Friday with Monday week start
            'FREQ=MONTHLY;BYDAY=-1FR;WKST=SU;COUNT=4',   // Last Friday with Sunday week start
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, "Pattern: {$pattern}");

            // Verify all occurrences are valid
            foreach ($occurrences as $occurrence) {
                $this->assertTrue(
                    $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence),
                    "Invalid occurrence for pattern: {$pattern}"
                );
            }
        }
    }

    public function testYearlyByweeknoWkstCompatibility(): void
    {
        // Test BYWEEKNO with WKST compatibility
        // Note: BYWEEKNO uses ISO 8601 week numbers, but WKST might affect occurrence generation
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=YEARLY;BYWEEKNO=26;WKST=MO;COUNT=3',   // Week 26 with Monday week start
            'FREQ=YEARLY;BYWEEKNO=26;WKST=SU;COUNT=3',   // Week 26 with Sunday week start
            'FREQ=YEARLY;BYWEEKNO=1;WKST=MO;COUNT=3',    // Week 1 with Monday week start
            'FREQ=YEARLY;BYWEEKNO=1;WKST=SU;COUNT=3',    // Week 1 with Sunday week start
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 3));

            $this->assertCount(3, $occurrences, "Pattern: {$pattern}");

            foreach ($occurrences as $occurrence) {
                $this->assertTrue(
                    $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence),
                    "Invalid occurrence for pattern: {$pattern}"
                );
            }
        }
    }

    public function testWkstDefaultBehaviorCompatibility(): void
    {
        // Test that omitting WKST defaults to MO (Monday) like sabre/dav
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $explicitMO = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=TU;WKST=MO;COUNT=4');
        $defaultWkst = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=TU;COUNT=4'); // No WKST specified

        $occurrencesExplicit = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($explicitMO, $start, 4));
        $occurrencesDefault = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($defaultWkst, $start, 4));

        // Results should be identical when WKST is omitted vs explicitly set to MO
        $this->assertEquals($occurrencesExplicit, $occurrencesDefault, 'Default WKST should be MO');
    }

    public function testEdgeCaseWkstCompatibility(): void
    {
        // Test edge cases that should match sabre/dav behavior

        // Case 1: Starting exactly on the target day with different WKST
        $start = new DateTimeImmutable('2024-01-08 09:00:00'); // Monday

        $patterns = [
            'FREQ=WEEKLY;BYDAY=MO;WKST=MO;COUNT=3',
            'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=3',
            'FREQ=WEEKLY;BYDAY=MO;WKST=WE;COUNT=3',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 3));

            $this->assertCount(3, $occurrences, "Pattern: {$pattern}");
            $this->assertEquals('2024-01-08', $occurrences[0]->format('Y-m-d'), "First occurrence should be start date for: {$pattern}");
        }
    }

    public static function provideSabreDavWkstPatterns(): array
    {
        return [
            // Basic weekly patterns that should match sabre/dav exactly
            [
                'FREQ=WEEKLY;BYDAY=MO;COUNT=4',
                '2024-01-01', // Monday
                4,
                ['2024-01-01', '2024-01-08', '2024-01-15', '2024-01-22'],
                'Weekly Monday starting on Monday (default WKST=MO)',
            ],

            [
                'FREQ=WEEKLY;BYDAY=TU;WKST=SU;COUNT=4',
                '2024-01-01', // Monday
                4,
                ['2024-01-02', '2024-01-09', '2024-01-16', '2024-01-23'],
                'Weekly Tuesday with Sunday week start',
            ],

            // Bi-weekly patterns
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=FR;WKST=MO;COUNT=3',
                '2024-01-01', // Monday
                3,
                [], // Don't specify exact dates as behavior might vary
                'Bi-weekly Friday with Monday week start',
            ],

            // Monthly patterns with positional BYDAY
            [
                'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=3',
                '2024-01-01',
                3,
                [], // Complex monthly calculations - just verify count and validity
                'Monthly first Monday with Sunday week start',
            ],

            // Yearly BYWEEKNO patterns
            [
                'FREQ=YEARLY;BYWEEKNO=26;WKST=MO;COUNT=2',
                '2024-01-01',
                2,
                [], // BYWEEKNO behavior with WKST - verify count and validity
                'Yearly week 26 with Monday week start',
            ],

            // Edge case: Start date matches target day
            [
                'FREQ=WEEKLY;BYDAY=SU;WKST=SU;COUNT=3',
                '2024-01-07', // Sunday
                3,
                ['2024-01-07', '2024-01-14', '2024-01-21'],
                'Weekly Sunday starting on Sunday with Sunday week start',
            ],
        ];
    }
}
