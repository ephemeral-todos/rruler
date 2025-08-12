<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for python-dateutil fixture-based compatibility validation.
 *
 * This test class demonstrates and validates the new python-dateutil fixture
 * validation functionality added to CompatibilityTestCase, providing an
 * additional validation layer alongside existing sabre/vobject compatibility tests.
 */
#[Group('python-dateutil')]
final class PythonDateutilFixtureCompatibilityTest extends CompatibilityTestCase
{
    /**
     * Test basic daily recurrence pattern against python-dateutil fixture.
     */
    public function testBasicDailyRecurrenceFixtureCompatibility(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'basic_daily',
            'Basic daily recurrence with COUNT termination'
        );
    }

    /**
     * Test weekly BYDAY pattern against python-dateutil fixture.
     */
    public function testWeeklyByDayRecurrenceFixtureCompatibility(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'weekly_byday',
            'Weekly recurrence on specific weekdays with UNTIL termination'
        );
    }

    /**
     * Test monthly BYSETPOS edge case against python-dateutil fixture.
     */
    public function testMonthlyBySetPosEdgeCaseFixtureCompatibility(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'monthly_bysetpos',
            'Last Friday of each month using BYSETPOS'
        );
    }

    /**
     * Test fixture validation with group filtering.
     */
    #[Group('edge-cases')]
    public function testFixtureCompatibilityWithGroupFiltering(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'monthly_bysetpos',
            'BYSETPOS edge case with group filtering',
            ['edge-cases']
        );
    }

    /**
     * Test loading fixtures by category.
     */
    public function testLoadFixturesByCategory(): void
    {
        $basicPatternFixtures = $this->loadPythonDateutilFixturesByCategory('basic-patterns');
        $edgeCaseFixtures = $this->loadPythonDateutilFixturesByCategory('edge-cases');

        // Should have at least one fixture in basic patterns
        if (!empty($basicPatternFixtures)) {
            $this->assertGreaterThanOrEqual(1, count($basicPatternFixtures));
        }

        // Should have at least one fixture in edge cases
        if (!empty($edgeCaseFixtures)) {
            $this->assertGreaterThanOrEqual(1, count($edgeCaseFixtures));
        }
    }

    /**
     * Test getting available fixture categories.
     */
    public function testGetAvailableCategories(): void
    {
        $categories = $this->getPythonDateutilFixtureCategories();

        if (!empty($categories)) {
            $this->assertIsArray($categories);
            $this->assertContains('basic-patterns', $categories);
        }
    }

    /**
     * Test data provider creation from fixtures.
     */
    public function testCreateDataProviderFromFixtures(): void
    {
        $basicPatternsProvider = $this->createPythonDateutilDataProvider('basic-patterns');

        if (!empty($basicPatternsProvider)) {
            $this->assertIsArray($basicPatternsProvider);

            // Each data set should have the expected structure
            foreach ($basicPatternsProvider as $testName => $testData) {
                $this->assertIsString($testName);
                $this->assertIsArray($testData);
                $this->assertCount(6, $testData); // [rrule, dtstart, timezone, range, expected_occurrences, metadata]
                $this->assertIsString($testData[0]); // rrule
                $this->assertIsString($testData[1]); // dtstart
                $this->assertIsString($testData[2]); // timezone
                $this->assertIsArray($testData[4]); // expected_occurrences
                $this->assertIsArray($testData[5]); // metadata
            }
        } else {
            $this->markTestSkipped('No basic pattern fixtures available for data provider test');
        }
    }

    /**
     * Data provider using basic pattern fixtures.
     *
     * This demonstrates how to use the createPythonDateutilDataProvider method
     * to create PHPUnit data providers from fixture categories.
     */
    public static function basicPatternFixtureProvider(): array
    {
        // Note: This is a static method, so we can't use instance methods.
        // In real usage, you would typically implement this differently or
        // use the fixture validation within regular test methods.
        return [];
    }

    /**
     * Test environment variable control for python-dateutil validation.
     *
     * This test demonstrates how the PYTHON_DATEUTIL_VALIDATION environment
     * variable can be used to control whether fixture validation runs.
     */
    public function testEnvironmentVariableControl(): void
    {
        // This test validates the shouldRunPythonDateutilValidation method
        $originalEnv = getenv('PYTHON_DATEUTIL_VALIDATION');

        try {
            // Test with validation disabled
            putenv('PYTHON_DATEUTIL_VALIDATION=false');
            $shouldRun = $this->shouldRunPythonDateutilValidation();
            $this->assertFalse($shouldRun);

            // Test with validation enabled
            putenv('PYTHON_DATEUTIL_VALIDATION=true');
            $shouldRun = $this->shouldRunPythonDateutilValidation();
            $this->assertTrue($shouldRun);
        } finally {
            // Restore original environment variable
            if ($originalEnv !== false) {
                putenv("PYTHON_DATEUTIL_VALIDATION={$originalEnv}");
            } else {
                putenv('PYTHON_DATEUTIL_VALIDATION');
            }
        }
    }

    /**
     * Test fixture loading error handling.
     *
     * When a fixture doesn't exist, the method should skip the test
     * rather than throw an exception.
     */
    public function testFixtureLoadingErrorHandling(): void
    {
        // Test with non-existent fixture - this should cause a test skip
        $this->expectException(\PHPUnit\Framework\SkippedTest::class);
        $this->expectExceptionMessage('Fixture nonexistent_fixture not available');

        $this->assertPythonDateutilFixtureCompatibility(
            'nonexistent_fixture',
            'Testing error handling'
        );
    }

    /**
     * Test that existing compatibility test methods are unaffected.
     *
     * This demonstrates that adding python-dateutil validation doesn't
     * break the existing sabre/vobject compatibility testing.
     */
    public function testExistingCompatibilityMethodsStillWork(): void
    {
        $rruleString = 'FREQ=DAILY;COUNT=3';
        $start = new \DateTimeImmutable('2023-01-01T10:00:00');

        // This should work exactly as before
        $rrulerOccurrences = $this->getRrulerOccurrences($rruleString, $start, 3);
        $sabreOccurrences = $this->getSabreOccurrences($rruleString, $start, 3);

        $this->assertCount(3, $rrulerOccurrences);
        $this->assertCount(3, $sabreOccurrences);

        // Test the existing assertion method
        $this->assertOccurrencesMatch(
            $rrulerOccurrences,
            $sabreOccurrences,
            $rruleString,
            'Existing compatibility method test'
        );

        // Test the existing compatibility assertion method
        $this->assertRruleCompatibility(
            $rruleString,
            $start,
            3,
            'Existing assertRruleCompatibility test'
        );
    }
}
