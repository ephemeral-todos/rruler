<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;

/**
 * Performance and regression testing for extended RRULE compatibility.
 *
 * This test class validates performance characteristics and prevents regressions
 * in complex RRULE patterns against sabre/vobject baseline.
 */
final class PerformanceRegressionTest extends CompatibilityTestCase
{
    /**
     * Test performance benchmark for complex RRULE patterns.
     *
     * Tests that complex patterns can be processed within reasonable time limits.
     */
    public function testComplexPatternPerformance(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Complex yearly pattern with multiple constraints
        $startTime = microtime(true);
        $this->assertRruleCompatibility(
            'FREQ=YEARLY;BYMONTH=1,3,5,7,9,11;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,-2,-1;COUNT=50',
            $start,
            50,
            'Complex yearly pattern performance test'
        );
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete within reasonable time (under 1 second)
        $this->assertLessThan(1.0, $duration, 'Complex pattern should complete within 1 second');
    }

    public function testLargeCountPerformance(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test with large count to ensure performance scales
        $startTime = microtime(true);
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;COUNT=100',
            $start,
            100,
            'Large count performance test'
        );
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        // Should complete within reasonable time
        $this->assertLessThan(2.0, $duration, 'Large count pattern should complete within 2 seconds');
    }

    /**
     * Test memory usage validation for large occurrence generation.
     *
     * Tests that memory usage remains reasonable for large datasets.
     */
    public function testMemoryUsageValidation(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        $memoryBefore = memory_get_usage(true);

        // Generate large set of occurrences
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=200',
            $start,
            200,
            'Memory usage validation test'
        );

        $memoryAfter = memory_get_usage(true);
        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should use less than 10MB for this test
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable');
    }

    /**
     * Test regression suite for existing functionality.
     *
     * Tests core patterns that should always work to prevent regressions.
     */
    public function testBasicPatternRegression(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Daily pattern
        $this->assertRruleCompatibility(
            'FREQ=DAILY;COUNT=7',
            $start,
            7,
            'Basic daily pattern regression'
        );

        // Weekly pattern
        $this->assertRruleCompatibility(
            'FREQ=WEEKLY;COUNT=4',
            $start,
            4,
            'Basic weekly pattern regression'
        );

        // Monthly pattern
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;COUNT=3',
            $start,
            3,
            'Basic monthly pattern regression'
        );
    }

    public function testBySetPosPatternRegression(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // First Monday of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=6',
            $start,
            6,
            'First Monday regression test'
        );

        // Last Friday of each month
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1;COUNT=6',
            $start,
            6,
            'Last Friday regression test'
        );
    }

    /**
     * Test performance comparison against sabre/dav baseline.
     *
     * Compares performance characteristics with sabre/dav implementation.
     */
    public function testPerformanceComparison(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $rruleString = 'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,-2,-1;COUNT=24';

        // Time Rruler implementation
        $startTime = microtime(true);
        $rrulerOccurrences = $this->getRrulerOccurrences($rruleString, $start, 24);
        $rrulerTime = microtime(true) - $startTime;

        // Time sabre/dav implementation
        $startTime = microtime(true);
        $sabreOccurrences = $this->getSabreOccurrences($rruleString, $start, 24);
        $sabreTime = microtime(true) - $startTime;

        // Results should match
        $this->assertOccurrencesMatch(
            $rrulerOccurrences,
            $sabreOccurrences,
            $rruleString,
            'Performance comparison test'
        );

        // Performance should be reasonable (within 10x of sabre/dav)
        $performanceRatio = $rrulerTime / max($sabreTime, 0.001); // Avoid division by zero
        $this->assertLessThan(10.0, $performanceRatio, 'Performance should be within 10x of sabre/dav');
    }

    /**
     * Test stress scenarios with multiple patterns.
     */
    public function testMultiplePatternStress(): void
    {
        $patterns = [
            'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=12',
            'FREQ=WEEKLY;BYDAY=TU,TH;COUNT=20',
            'FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=12',
            'FREQ=YEARLY;BYMONTH=6,12;COUNT=4',
        ];

        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        $startTime = microtime(true);

        foreach ($patterns as $i => $pattern) {
            $expectedCount = intval(explode('COUNT=', $pattern)[1]);
            $this->assertRruleCompatibility(
                $pattern,
                $start,
                $expectedCount,
                "Stress test pattern $i"
            );
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // All patterns should complete within reasonable time
        $this->assertLessThan(5.0, $totalTime, 'Multiple patterns should complete within 5 seconds');
    }

    /**
     * Test edge case performance scenarios.
     */
    public function testEdgeCasePerformance(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');

        // Test with many BYSETPOS values
        $startTime = microtime(true);
        $this->assertRruleCompatibility(
            'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,3,4,5,-5,-4,-3,-2,-1;COUNT=20',
            $start,
            20,
            'Many BYSETPOS values performance'
        );
        $endTime = microtime(true);
        $duration = $endTime - $startTime;

        $this->assertLessThan(1.5, $duration, 'Many BYSETPOS values should complete within 1.5 seconds');
    }

    /**
     * Test consistency across multiple runs.
     *
     * Ensures results are deterministic and consistent.
     */
    public function testConsistencyValidation(): void
    {
        $start = new DateTimeImmutable('2025-01-01 10:00:00');
        $rruleString = 'FREQ=MONTHLY;BYDAY=WE;BYSETPOS=2;COUNT=6';

        // Run the same pattern multiple times
        $firstRun = $this->getRrulerOccurrences($rruleString, $start, 6);
        $secondRun = $this->getRrulerOccurrences($rruleString, $start, 6);
        $thirdRun = $this->getRrulerOccurrences($rruleString, $start, 6);

        // All runs should produce identical results
        $this->assertEquals($firstRun, $secondRun, 'First and second runs should be identical');
        $this->assertEquals($secondRun, $thirdRun, 'Second and third runs should be identical');
        $this->assertEquals($firstRun, $thirdRun, 'First and third runs should be identical');
    }
}
