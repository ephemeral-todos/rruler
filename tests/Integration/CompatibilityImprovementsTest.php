<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests that verify end-to-end compatibility improvements.
 *
 * These tests validate that the major fixes implemented in the compatibility
 * fixes spec are working correctly in real-world scenarios.
 */
final class CompatibilityImprovementsTest extends TestCase
{
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    /**
     * Integration test for Issue 1: BYDAY Time Preservation Bug (RESOLVED).
     *
     * Validates that time components are preserved across all frequency types
     * and complex patterns in real-world usage scenarios.
     */
    public function testTimePreservationAcrossAllPatterns(): void
    {
        $testCases = [
            // Weekly BYDAY pattern - original failing case
            [
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6',
                'start' => '2025-01-01 14:30:45',
                'description' => 'Weekly BYDAY time preservation',
            ],
            // Monthly pattern with time
            [
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=15;COUNT=4',
                'start' => '2025-01-15 09:15:30',
                'description' => 'Monthly BYMONTHDAY time preservation',
            ],
            // Yearly pattern with time
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=6;BYMONTHDAY=21;COUNT=3',
                'start' => '2025-06-21 18:45:00',
                'description' => 'Yearly pattern time preservation',
            ],
            // Complex pattern with microseconds
            [
                'rrule' => 'FREQ=DAILY;INTERVAL=3;COUNT=5',
                'start' => '2025-01-01 10:30:45.123456',
                'description' => 'Complex pattern with microseconds',
            ],
        ];

        foreach ($testCases as $testCase) {
            $start = new DateTimeImmutable($testCase['start']);
            $rruleObj = $this->rruler->parse($testCase['rrule']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));

            foreach ($occurrences as $index => $occurrence) {
                // All occurrences should preserve the exact time from start
                $this->assertEquals(
                    $start->format('H:i:s.u'),
                    $occurrence->format('H:i:s.u'),
                    sprintf(
                        '%s: Occurrence %d should preserve time %s, got %s',
                        $testCase['description'],
                        $index,
                        $start->format('H:i:s.u'),
                        $occurrence->format('H:i:s.u')
                    )
                );
            }
        }
    }

    /**
     * Integration test for Issue 2: Monthly Date Boundary Handling (RESOLVED).
     *
     * Validates that monthly patterns correctly handle invalid dates by skipping
     * months appropriately (e.g., Feb 31st -> Mar 31st).
     */
    public function testMonthlyDateBoundaryHandling(): void
    {
        $testCases = [
            // Original failing case: Dec 31st monthly
            [
                'start' => '2025-12-31 10:00:00',
                'rrule' => 'FREQ=MONTHLY;COUNT=4',
                'expectedDates' => ['2025-12-31', '2026-01-31', '2026-03-31', '2026-05-31'],
                'description' => 'Dec 31st monthly - skip February',
            ],
            // Jan 30th monthly - skip February
            [
                'start' => '2025-01-30 15:30:00',
                'rrule' => 'FREQ=MONTHLY;COUNT=4',
                'expectedDates' => ['2025-01-30', '2025-03-30', '2025-04-30', '2025-05-30'],
                'description' => 'Jan 30th monthly - skip February',
            ],
            // Jan 31st with longer sequence
            [
                'start' => '2025-01-31 09:00:00',
                'rrule' => 'FREQ=MONTHLY;COUNT=6',
                'expectedDates' => ['2025-01-31', '2025-03-31', '2025-05-31', '2025-07-31', '2025-08-31', '2025-10-31'],
                'description' => 'Jan 31st - skip months without 31 days',
            ],
        ];

        foreach ($testCases as $testCase) {
            $start = new DateTimeImmutable($testCase['start']);
            $rruleObj = $this->rruler->parse($testCase['rrule']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));

            $this->assertCount(
                count($testCase['expectedDates']),
                $occurrences,
                sprintf('%s: Should generate %d occurrences', $testCase['description'], count($testCase['expectedDates']))
            );

            foreach ($testCase['expectedDates'] as $index => $expectedDate) {
                $this->assertEquals(
                    $expectedDate,
                    $occurrences[$index]->format('Y-m-d'),
                    sprintf(
                        '%s: Occurrence %d should be %s, got %s',
                        $testCase['description'],
                        $index,
                        $expectedDate,
                        $occurrences[$index]->format('Y-m-d')
                    )
                );

                // Also verify time is preserved
                $this->assertEquals(
                    $start->format('H:i:s'),
                    $occurrences[$index]->format('H:i:s'),
                    sprintf('%s: Time should be preserved for occurrence %d', $testCase['description'], $index)
                );
            }
        }
    }

    /**
     * Integration test for Issue 3: Leap Year Yearly Recurrence (RESOLVED).
     *
     * Validates that yearly patterns starting on Feb 29th only occur in leap years
     * and skip non-leap years appropriately.
     */
    public function testLeapYearYearlyRecurrence(): void
    {
        $testCases = [
            // Original failing case: Feb 29th yearly
            [
                'start' => '2024-02-29 12:00:00',
                'rrule' => 'FREQ=YEARLY;COUNT=5',
                'expectedYears' => [2024, 2028, 2032, 2036, 2040],
                'description' => 'Feb 29th yearly - only leap years',
            ],
            // Another leap year sequence starting different year
            [
                'start' => '2020-02-29 18:30:00',
                'rrule' => 'FREQ=YEARLY;COUNT=4',
                'expectedYears' => [2020, 2024, 2028, 2032],
                'description' => 'Feb 29th from 2020 - leap years only',
            ],
        ];

        foreach ($testCases as $testCase) {
            $start = new DateTimeImmutable($testCase['start']);
            $rruleObj = $this->rruler->parse($testCase['rrule']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));

            $this->assertCount(
                count($testCase['expectedYears']),
                $occurrences,
                sprintf('%s: Should generate %d occurrences', $testCase['description'], count($testCase['expectedYears']))
            );

            foreach ($testCase['expectedYears'] as $index => $expectedYear) {
                $this->assertEquals(
                    $expectedYear,
                    (int) $occurrences[$index]->format('Y'),
                    sprintf(
                        '%s: Occurrence %d should be in year %d, got %d',
                        $testCase['description'],
                        $index,
                        $expectedYear,
                        (int) $occurrences[$index]->format('Y')
                    )
                );

                // Verify it's always Feb 29th
                $this->assertEquals(
                    '02-29',
                    $occurrences[$index]->format('m-d'),
                    sprintf('%s: Should always be Feb 29th for occurrence %d', $testCase['description'], $index)
                );

                // Verify time is preserved
                $this->assertEquals(
                    $start->format('H:i:s'),
                    $occurrences[$index]->format('H:i:s'),
                    sprintf('%s: Time should be preserved for occurrence %d', $testCase['description'], $index)
                );
            }
        }
    }

    /**
     * Integration test for Weekly BYSETPOS RFC 5545 Compliance.
     *
     * Validates that weekly BYSETPOS patterns work correctly according to RFC 5545
     * specification (intentionally different from sabre/vobject).
     */
    public function testWeeklyBySetPosRfc5545Compliance(): void
    {
        $testCases = [
            // Basic BYSETPOS=1 (first in BYDAY order)
            [
                'start' => '2025-01-01 10:00:00', // Wednesday
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1;COUNT=4',
                'expectedDates' => ['2025-01-01', '2025-01-06', '2025-01-13', '2025-01-20'],
                'description' => 'Weekly BYSETPOS=1 - first in BYDAY order each week',
            ],
            // BYSETPOS=-1 (last in BYDAY order)
            [
                'start' => '2025-01-01 10:00:00', // Wednesday
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=-1;COUNT=4',
                'expectedDates' => ['2025-01-03', '2025-01-10', '2025-01-17', '2025-01-24'],
                'description' => 'Weekly BYSETPOS=-1 - last in BYDAY order each week',
            ],
            // Multiple BYSETPOS values
            [
                'start' => '2025-01-06 10:00:00', // Monday
                'rrule' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1,-1;COUNT=6',
                'expectedDates' => ['2025-01-06', '2025-01-10', '2025-01-13', '2025-01-17', '2025-01-20', '2025-01-24'],
                'description' => 'Weekly BYSETPOS=1,-1 - first (Mon) and last (Fri) in each week',
            ],
        ];

        foreach ($testCases as $testCase) {
            $start = new DateTimeImmutable($testCase['start']);
            $rruleObj = $this->rruler->parse($testCase['rrule']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));

            $this->assertCount(
                count($testCase['expectedDates']),
                $occurrences,
                sprintf('%s: Should generate %d occurrences', $testCase['description'], count($testCase['expectedDates']))
            );

            foreach ($testCase['expectedDates'] as $index => $expectedDate) {
                $this->assertEquals(
                    $expectedDate,
                    $occurrences[$index]->format('Y-m-d'),
                    sprintf(
                        '%s: Occurrence %d should be %s, got %s',
                        $testCase['description'],
                        $index,
                        $expectedDate,
                        $occurrences[$index]->format('Y-m-d')
                    )
                );
            }
        }
    }

    /**
     * Integration test for combined fixes working together.
     *
     * Tests complex scenarios that involve multiple resolved issues to ensure
     * they work correctly when combined.
     */
    public function testCombinedFixesIntegration(): void
    {
        // Complex scenario: Yearly leap day pattern with time preservation
        // This combines leap year handling + time preservation fixes
        $start = new DateTimeImmutable('2024-02-29 14:30:15.123456');
        $rruleObj = $this->rruler->parse('FREQ=YEARLY;COUNT=4');
        $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));

        // Should only occur in leap years and preserve time
        $expectedDates = [
            '2024-02-29', // Original (leap year)
            '2028-02-29', // Next leap year - skips 2025, 2026, 2027
            '2032-02-29', // Next leap year - skips 2029, 2030, 2031
            '2036-02-29',  // Next leap year - skips 2033, 2034, 2035
        ];

        $this->assertCount(4, $occurrences, 'Should generate exactly 4 leap year occurrences');

        foreach ($expectedDates as $index => $expectedDate) {
            // Verify date is correct (leap year handling)
            $this->assertEquals(
                $expectedDate,
                $occurrences[$index]->format('Y-m-d'),
                sprintf('Occurrence %d should be %s, got %s', $index, $expectedDate, $occurrences[$index]->format('Y-m-d'))
            );

            // Verify time is preserved (time preservation fix)
            $this->assertEquals(
                '14:30:15.123456',
                $occurrences[$index]->format('H:i:s.u'),
                sprintf('Occurrence %d should preserve time 14:30:15.123456, got %s', $index, $occurrences[$index]->format('H:i:s.u'))
            );
        }

        // Additional test: Monthly date boundary handling with time preservation
        $start2 = new DateTimeImmutable('2025-01-31 09:15:30');
        $rruleObj2 = $this->rruler->parse('FREQ=MONTHLY;COUNT=4');
        $occurrences2 = iterator_to_array($this->generator->generateOccurrences($rruleObj2, $start2, null, null));

        // Should skip February (no 31st) and preserve time
        $expectedDates2 = ['2025-01-31', '2025-03-31', '2025-05-31', '2025-07-31'];

        foreach ($expectedDates2 as $index => $expectedDate) {
            $this->assertEquals(
                $expectedDate,
                $occurrences2[$index]->format('Y-m-d'),
                sprintf('Monthly boundary: Occurrence %d should be %s, got %s', $index, $expectedDate, $occurrences2[$index]->format('Y-m-d'))
            );

            $this->assertEquals(
                '09:15:30',
                $occurrences2[$index]->format('H:i:s'),
                sprintf('Monthly boundary: Time should be preserved for occurrence %d', $index)
            );
        }
    }

    /**
     * Integration test for performance with large occurrence sets.
     *
     * Ensures that fixes don't negatively impact performance for complex patterns.
     */
    public function testPerformanceWithComplexPatterns(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test pattern that generates many occurrences
        $rruleObj = $this->rruler->parse('FREQ=DAILY;INTERVAL=1;COUNT=365');

        $startTime = microtime(true);
        $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleObj, $start, null, null));
        $duration = microtime(true) - $startTime;

        // Performance assertions
        $this->assertCount(365, $occurrences, 'Should generate exactly 365 daily occurrences');
        $this->assertLessThan(1.0, $duration, 'Should generate 365 occurrences in under 1 second');

        // Verify all have correct time preservation
        foreach ($occurrences as $index => $occurrence) {
            $this->assertEquals(
                '10:00:00',
                $occurrence->format('H:i:s'),
                sprintf('Occurrence %d should preserve time 10:00:00', $index)
            );
        }
    }
}
