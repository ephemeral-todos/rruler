<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility\Performance;

use EphemeralTodos\Rruler\Testing\TestCase\CompatibilityTestCase;

/**
 * Fixture Validation Performance Test.
 *
 * This test class validates that fixture-based validation meets performance
 * requirements and doesn't introduce significant overhead compared to
 * standard sabre/vobject compatibility testing.
 */
final class FixtureValidationPerformanceTest extends CompatibilityTestCase
{
    private const PERFORMANCE_TARGET_MS = 100; // Max 100ms per fixture validation
    private const BATCH_PERFORMANCE_TARGET_MS = 500; // Max 500ms for batch operations

    /**
     * Test individual fixture validation performance.
     *
     * Validates that individual fixture validations complete within target time.
     */
    public function testIndividualFixtureValidationPerformance(): void
    {
        $startTime = microtime(true);

        $this->assertPythonDateutilFixtureCompatibility(
            'basic_daily',
            'Performance test - basic daily pattern'
        );

        $endTime = microtime(true);
        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            self::PERFORMANCE_TARGET_MS,
            $executionTimeMs,
            "Individual fixture validation took {$executionTimeMs}ms, target is ".self::PERFORMANCE_TARGET_MS.'ms'
        );
    }

    /**
     * Test batch fixture validation performance.
     *
     * Validates that multiple fixture validations in sequence meet performance targets.
     */
    public function testBatchFixtureValidationPerformance(): void
    {
        $startTime = microtime(true);

        // Run multiple fixture validations to test batch performance
        $this->assertPythonDateutilFixtureCompatibility(
            'basic_daily',
            'Batch test 1'
        );

        $this->assertPythonDateutilFixtureCompatibility(
            'weekly_byday',
            'Batch test 2'
        );

        $this->assertPythonDateutilFixtureCompatibility(
            'monthly_bysetpos',
            'Batch test 3'
        );

        $endTime = microtime(true);
        $executionTimeMs = ($endTime - $startTime) * 1000;

        $this->assertLessThan(
            self::BATCH_PERFORMANCE_TARGET_MS,
            $executionTimeMs,
            "Batch fixture validation took {$executionTimeMs}ms, target is ".self::BATCH_PERFORMANCE_TARGET_MS.'ms'
        );
    }

    /**
     * Test fixture loading cache effectiveness.
     *
     * Validates that subsequent fixture loads are faster due to caching.
     */
    public function testFixtureLoadingCacheEffectiveness(): void
    {
        // First load (should be slower - involves file I/O)
        $startTime1 = microtime(true);
        $this->assertPythonDateutilFixtureCompatibility(
            'complex_bysetpos_patterns',
            'Cache test - first load'
        );
        $endTime1 = microtime(true);
        $firstLoadTime = ($endTime1 - $startTime1) * 1000;

        // Second load (should be faster due to caching)
        $startTime2 = microtime(true);
        $this->assertPythonDateutilFixtureCompatibility(
            'complex_bysetpos_patterns',
            'Cache test - second load'
        );
        $endTime2 = microtime(true);
        $secondLoadTime = ($endTime2 - $startTime2) * 1000;

        // Second load should be significantly faster (allow for some variance)
        $this->assertLessThanOrEqual(
            $firstLoadTime,
            $secondLoadTime,
            "Second fixture load ({$secondLoadTime}ms) should not be slower than first load ({$firstLoadTime}ms)"
        );

        // Both should still meet individual performance targets
        $this->assertLessThan(self::PERFORMANCE_TARGET_MS, $firstLoadTime);
        $this->assertLessThan(self::PERFORMANCE_TARGET_MS, $secondLoadTime);
    }

    /**
     * Test performance comparison between fixture validation and sabre/vobject testing.
     *
     * Validates that fixture-based validation doesn't introduce excessive overhead.
     */
    public function testFixtureVsSabrePerformanceComparison(): void
    {
        // Test sabre/vobject compatibility performance
        $startTimeSabre = microtime(true);
        $this->assertRruleCompatibility(
            'FREQ=DAILY;COUNT=5',
            new \DateTimeImmutable('2025-01-01 10:00:00'),
            5,
            'Sabre performance test'
        );
        $endTimeSabre = microtime(true);
        $sabreTime = ($endTimeSabre - $startTimeSabre) * 1000;

        // Test fixture validation performance
        $startTimeFixture = microtime(true);
        $this->assertPythonDateutilFixtureCompatibility(
            'basic_daily',
            'Fixture performance test'
        );
        $endTimeFixture = microtime(true);
        $fixtureTime = ($endTimeFixture - $startTimeFixture) * 1000;

        // Fixture validation should not be more than 3x slower than sabre testing
        $maxAllowedFixtureTime = $sabreTime * 3;
        $this->assertLessThan(
            $maxAllowedFixtureTime,
            $fixtureTime,
            "Fixture validation ({$fixtureTime}ms) should not be more than 3x slower than sabre testing ({$sabreTime}ms)"
        );
    }

    /**
     * Test memory usage during fixture validation.
     *
     * Validates that fixture validation doesn't consume excessive memory.
     */
    public function testFixtureValidationMemoryUsage(): void
    {
        $initialMemory = memory_get_usage(true);

        // Run several fixture validations
        for ($i = 0; $i < 5; ++$i) {
            $this->assertPythonDateutilFixtureCompatibility(
                'basic_daily',
                "Memory test iteration {$i}"
            );
        }

        $finalMemory = memory_get_usage(true);
        $memoryIncrease = $finalMemory - $initialMemory;

        // Memory increase should be reasonable (less than 5MB for 5 fixture validations)
        $maxAllowedMemoryIncrease = 5 * 1024 * 1024; // 5MB
        $this->assertLessThan(
            $maxAllowedMemoryIncrease,
            $memoryIncrease,
            'Memory increase of '.number_format($memoryIncrease / 1024 / 1024, 2).'MB exceeds target of 5MB'
        );
    }

    /**
     * Test fixture validation performance regression.
     *
     * Validates that fixture validation maintains consistent performance over time.
     */
    public function testFixtureValidationPerformanceRegression(): void
    {
        $executionTimes = [];

        // Run the same fixture validation multiple times
        for ($i = 0; $i < 10; ++$i) {
            $startTime = microtime(true);
            $this->assertPythonDateutilFixtureCompatibility(
                'boundary_conditions',
                "Regression test iteration {$i}"
            );
            $endTime = microtime(true);
            $executionTimes[] = ($endTime - $startTime) * 1000;
        }

        // Calculate statistics
        $avgTime = array_sum($executionTimes) / count($executionTimes);
        $maxTime = max($executionTimes);
        $minTime = min($executionTimes);

        // Validate consistent performance
        $this->assertLessThan(
            self::PERFORMANCE_TARGET_MS,
            $avgTime,
            "Average execution time ({$avgTime}ms) exceeds target of ".self::PERFORMANCE_TARGET_MS.'ms'
        );

        $this->assertLessThan(
            self::PERFORMANCE_TARGET_MS * 2,
            $maxTime,
            "Maximum execution time ({$maxTime}ms) exceeds 2x target of ".(self::PERFORMANCE_TARGET_MS * 2).'ms'
        );

        // Variance should be reasonable (max should not be more than 3x min)
        $this->assertLessThan(
            $minTime * 3,
            $maxTime,
            "Performance variance too high - max ({$maxTime}ms) is more than 3x min ({$minTime}ms)"
        );
    }
}
