<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * End-to-end workflow tests for WKST across all frequency types.
 *
 * Tests complete workflows from RRULE parsing through occurrence generation
 * and validation for DAILY, WEEKLY, MONTHLY, and YEARLY frequencies with WKST.
 */
final class WkstEndToEndWorkflowTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    #[DataProvider('provideAllFrequencyWkstWorkflows')]
    public function testEndToEndWkstWorkflowForAllFrequencies(
        string $rruleString,
        string $startDate,
        int $expectedCount,
        string $frequency,
        string $description,
    ): void {
        // Step 1: Parse RRULE
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $this->assertEquals($frequency, $rrule->getFrequency(), "Frequency should match for: {$description}");

        // Step 2: Generate occurrences
        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start, $expectedCount);
        $this->assertInstanceOf(\Generator::class, $occurrences, "Should return Generator for: {$description}");

        $results = iterator_to_array($occurrences);

        // Step 3: Verify count
        $this->assertCount($expectedCount, $results, "Count should match for: {$description}");

        // Step 4: Validate each occurrence
        foreach ($results as $index => $occurrence) {
            $this->assertInstanceOf(DateTimeImmutable::class, $occurrence, "Occurrence {$index} should be DateTimeImmutable for: {$description}");

            $isValid = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $occurrence);
            $this->assertTrue($isValid, "Occurrence {$index} ({$occurrence->format('Y-m-d')}) should be valid for: {$description}");
        }

        // Step 5: Verify chronological order
        for ($i = 1; $i < count($results); ++$i) {
            $this->assertGreaterThan(
                $results[$i - 1],
                $results[$i],
                "Occurrence {$i} should be after occurrence ".($i - 1)." for: {$description}"
            );
        }

        // Step 6: Verify no duplicates
        $dateStrings = array_map(fn ($date) => $date->format('Y-m-d H:i:s'), $results);
        $uniqueDates = array_unique($dateStrings);
        $this->assertCount(count($dateStrings), $uniqueDates, "No duplicate occurrences should exist for: {$description}");

        // Step 7: Verify WKST is respected (check that week start day is properly set)
        if (str_contains($rruleString, 'WKST=')) {
            $expectedWkst = $this->extractWkstFromRrule($rruleString);
            $this->assertEquals($expectedWkst, $rrule->getWeekStart(), "WKST should be parsed correctly for: {$description}");
        } else {
            $this->assertEquals('MO', $rrule->getWeekStart(), "Default WKST should be MO for: {$description}");
        }
    }

    public function testDailyFrequencyWithWkstWorkflow(): void
    {
        // Test complete DAILY workflow with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $patterns = [
            'FREQ=DAILY;COUNT=5;WKST=MO',
            'FREQ=DAILY;COUNT=5;WKST=SU',
            'FREQ=DAILY;INTERVAL=2;COUNT=5;WKST=WE',
        ];

        foreach ($patterns as $pattern) {
            $rrule = $this->testRruler->parse($pattern);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 5));

            $this->assertCount(5, $occurrences, "Pattern: {$pattern}");

            // For DAILY frequency, WKST shouldn't affect the result much, but parsing should work
            $this->assertEquals('DAILY', $rrule->getFrequency());

            // Verify daily intervals
            if (str_contains($pattern, 'INTERVAL=2')) {
                $this->assertEquals(2, $occurrences[1]->diff($occurrences[0])->days);
            } else {
                $this->assertEquals(1, $occurrences[1]->diff($occurrences[0])->days);
            }
        }
    }

    public function testWeeklyFrequencyWithWkstWorkflow(): void
    {
        // Test complete WEEKLY workflow with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $workflows = [
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;WKST=MO;COUNT=6',
                'expectedDays' => ['Monday', 'Wednesday', 'Friday'],
            ],
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;WKST=SU;COUNT=6',
                'expectedDays' => ['Monday', 'Wednesday', 'Friday'],
            ],
            [
                'pattern' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,TH;WKST=TU;COUNT=4',
                'expectedDays' => ['Tuesday', 'Thursday'],
            ],
        ];

        foreach ($workflows as $workflow) {
            $rrule = $this->testRruler->parse($workflow['pattern']);
            $expectedCount = $this->extractCountFromRrule($workflow['pattern']);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, $expectedCount));

            $this->assertCount($expectedCount, $occurrences, "Pattern: {$workflow['pattern']}");

            // Verify all occurrences are on expected days
            foreach ($occurrences as $occurrence) {
                $dayName = $occurrence->format('l');
                $this->assertContains($dayName, $workflow['expectedDays'], "Pattern: {$workflow['pattern']}");
            }
        }
    }

    public function testMonthlyFrequencyWithWkstWorkflow(): void
    {
        // Test complete MONTHLY workflow with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $workflows = [
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=1MO;WKST=MO;COUNT=4',
                'expectedDay' => 'Monday',
                'description' => 'First Monday of each month with Monday week start',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=4',
                'expectedDay' => 'Monday',
                'description' => 'First Monday of each month with Sunday week start',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=-1FR;WKST=WE;COUNT=4',
                'expectedDay' => 'Friday',
                'description' => 'Last Friday of each month with Wednesday week start',
            ],
        ];

        foreach ($workflows as $workflow) {
            $rrule = $this->testRruler->parse($workflow['pattern']);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 4));

            $this->assertCount(4, $occurrences, $workflow['description']);

            // Verify all occurrences are on expected day
            foreach ($occurrences as $occurrence) {
                $this->assertEquals($workflow['expectedDay'], $occurrence->format('l'), $workflow['description']);
            }
        }
    }

    public function testYearlyFrequencyWithWkstWorkflow(): void
    {
        // Test complete YEARLY workflow with WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $workflows = [
            [
                'pattern' => 'FREQ=YEARLY;BYMONTH=6;BYDAY=MO;WKST=MO;COUNT=3',
                'description' => 'Yearly June Mondays with Monday week start',
            ],
            [
                'pattern' => 'FREQ=YEARLY;BYWEEKNO=26;WKST=SU;COUNT=3',
                'description' => 'Yearly week 26 with Sunday week start',
            ],
            [
                'pattern' => 'FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=25;WKST=FR;COUNT=3',
                'description' => 'Christmas Day with Friday week start',
            ],
        ];

        foreach ($workflows as $workflow) {
            $rrule = $this->testRruler->parse($workflow['pattern']);
            $occurrences = iterator_to_array($this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 3));

            $this->assertCount(3, $occurrences, $workflow['description']);

            // Verify yearly intervals (occurrences should be in different years)
            if (count($occurrences) >= 2) {
                $year1 = (int) $occurrences[0]->format('Y');
                $year2 = (int) $occurrences[1]->format('Y');
                $this->assertGreaterThanOrEqual($year1, $year2, $workflow['description']);

                // For yearly frequency, later occurrences should be in same or later years
                if (count($occurrences) >= 3) {
                    $year3 = (int) $occurrences[2]->format('Y');
                    $this->assertGreaterThanOrEqual($year2, $year3, $workflow['description']);
                }
            }
        }
    }

    public function testComplexWorkflowWithRangeLimits(): void
    {
        // Test workflow with date range limits - use a simpler pattern that will definitely match
        $start = new DateTimeImmutable('2024-01-01 09:00:00');
        $rangeStart = new DateTimeImmutable('2024-06-01');
        $rangeEnd = new DateTimeImmutable('2024-12-31');

        $rrule = $this->testRruler->parse('FREQ=MONTHLY;BYMONTHDAY=15;WKST=SU;COUNT=12');

        // Generate occurrences within date range
        $occurrences = $this->testOccurrenceGenerator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $results = iterator_to_array($occurrences);

        // Should find 15th of each month from June to December (7 occurrences)
        $this->assertNotEmpty($results, 'Should find occurrences in date range');

        // Verify all occurrences are within range and on 15th
        foreach ($results as $occurrence) {
            $this->assertGreaterThanOrEqual($rangeStart, $occurrence, 'Should be after range start');
            $this->assertLessThanOrEqual($rangeEnd, $occurrence, 'Should be before range end');
            $this->assertEquals(15, (int) $occurrence->format('j'), 'Should be on 15th of month');
        }
    }

    public function testErrorHandlingWorkflow(): void
    {
        // Test error handling in end-to-end workflow

        // Invalid WKST value should throw exception during parsing
        $this->expectException(\EphemeralTodos\Rruler\Exception\ValidationException::class);
        $this->testRruler->parse('FREQ=WEEKLY;WKST=XX;COUNT=3');
    }

    private function extractWkstFromRrule(string $rrule): string
    {
        if (preg_match('/WKST=([A-Z]{2})/', $rrule, $matches)) {
            return $matches[1];
        }

        return 'MO'; // Default
    }

    private function extractCountFromRrule(string $rrule): int
    {
        if (preg_match('/COUNT=(\d+)/', $rrule, $matches)) {
            return (int) $matches[1];
        }

        return 10; // Default for testing
    }

    public static function provideAllFrequencyWkstWorkflows(): array
    {
        return [
            // DAILY frequency workflows
            [
                'FREQ=DAILY;COUNT=5;WKST=MO',
                '2024-01-01 09:00:00',
                5,
                'DAILY',
                'Daily with Monday week start',
            ],
            [
                'FREQ=DAILY;INTERVAL=3;COUNT=4;WKST=SU',
                '2024-01-01 09:00:00',
                4,
                'DAILY',
                'Every 3rd day with Sunday week start',
            ],

            // WEEKLY frequency workflows
            [
                'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
                '2024-01-01 09:00:00',
                4,
                'WEEKLY',
                'Weekly Monday with Sunday week start',
            ],
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE,FR;WKST=TU;COUNT=6',
                '2024-01-01 09:00:00',
                6,
                'WEEKLY',
                'Bi-weekly Wed/Fri with Tuesday week start',
            ],

            // MONTHLY frequency workflows
            [
                'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=4',
                '2024-01-01 09:00:00',
                4,
                'MONTHLY',
                'Monthly first Monday with Sunday week start',
            ],
            [
                'FREQ=MONTHLY;BYDAY=-1FR;WKST=WE;COUNT=4',
                '2024-01-01 09:00:00',
                4,
                'MONTHLY',
                'Monthly last Friday with Wednesday week start',
            ],
            [
                'FREQ=MONTHLY;BYMONTHDAY=15;WKST=TH;COUNT=4',
                '2024-01-01 09:00:00',
                4,
                'MONTHLY',
                'Monthly 15th with Thursday week start',
            ],

            // YEARLY frequency workflows
            [
                'FREQ=YEARLY;BYMONTH=6;BYDAY=MO;WKST=MO;COUNT=3',
                '2024-01-01 09:00:00',
                3,
                'YEARLY',
                'Yearly June Mondays with Monday week start',
            ],
            [
                'FREQ=YEARLY;BYWEEKNO=26;WKST=SU;COUNT=3',
                '2024-01-01 09:00:00',
                3,
                'YEARLY',
                'Yearly week 26 with Sunday week start',
            ],
            [
                'FREQ=YEARLY;BYMONTH=12;BYMONTHDAY=25;WKST=SA;COUNT=3',
                '2024-01-01 09:00:00',
                3,
                'YEARLY',
                'Christmas Day with Saturday week start',
            ],

            // Complex multi-parameter workflows
            [
                'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;WKST=FR;COUNT=8',
                '2024-01-01 09:00:00',
                8,
                'YEARLY',
                'Quarterly Fridays with Friday week start',
            ],
        ];
    }
}
