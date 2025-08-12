<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use EphemeralTodos\Rruler\Testing\Fixtures\YamlFixtureLoader;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;

/**
 * Abstract base class for compatibility testing between Rruler and sabre/dav.
 *
 * This class provides utilities for comparing RRULE parsing and occurrence generation
 * results between our implementation and the sabre/vobject library.
 */
abstract class CompatibilityTestCase extends TestCase
{
    protected Rruler $rruler;
    protected DefaultOccurrenceGenerator $occurrenceGenerator;
    protected ?YamlFixtureLoader $pythonDateutilFixtureLoader = null;
    /** @var array<string, mixed> */
    private static array $preloadedFixtures = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->rruler = new Rruler();
        $this->occurrenceGenerator = new DefaultOccurrenceGenerator();

        // Initialize python-dateutil fixture loader if fixtures exist
        $fixturesPath = __DIR__.'/../fixtures/python-dateutil/generated';
        if (is_dir($fixturesPath)) {
            $this->pythonDateutilFixtureLoader = new YamlFixtureLoader($fixturesPath);
        }
    }

    /**
     * Parse an RRULE using Rruler and generate occurrences.
     *
     * @param string $rruleString The RRULE string to parse
     * @param DateTimeImmutable $start The start date for occurrence generation
     * @param int|null $limit Maximum number of occurrences to generate
     * @return array<DateTimeImmutable>
     */
    protected function getRrulerOccurrences(
        string $rruleString,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): array {
        $rrule = $this->rruler->parse($rruleString);

        // For compatibility testing, we need to simulate iCalendar behavior
        // where the start date (DTSTART) is always included as the first occurrence
        // This matches sabre/vobject's EventIterator behavior

        // Check if the start date would naturally be included by generating one occurrence
        $testOccurrences = iterator_to_array($this->occurrenceGenerator->generateOccurrences($rrule, $start, 1));
        $startDateMatches = !empty($testOccurrences) && $testOccurrences[0]->format('Y-m-d H:i:s') === $start->format('Y-m-d H:i:s');

        if ($startDateMatches) {
            // Start date naturally matches the RRULE, use normal generation
            $occurrences = $this->occurrenceGenerator->generateOccurrences($rrule, $start, $limit);

            return iterator_to_array($occurrences);
        } else {
            // Start date doesn't match RRULE, include it first then get subsequent matches
            $occurrences = [];
            $count = 0;

            // Always include the start date first (RFC 5545 iCalendar behavior)
            if ($limit === null || $count < $limit) {
                $occurrences[] = $start;
                ++$count;
            }

            // Generate subsequent occurrences
            if ($limit === null || $count < $limit) {
                $remainingLimit = ($limit !== null) ? $limit - $count : null;
                $subsequentOccurrences = $this->occurrenceGenerator->generateOccurrences($rrule, $start, $remainingLimit);

                foreach ($subsequentOccurrences as $occurrence) {
                    if ($limit !== null && $count >= $limit) {
                        break;
                    }

                    $occurrences[] = $occurrence;
                    ++$count;
                }
            }

            return $occurrences;
        }
    }

    /**
     * Parse an RRULE using sabre/vobject and generate occurrences.
     *
     * @param string $rruleString The RRULE string to parse
     * @param DateTimeImmutable $start The start date for occurrence generation
     * @param int|null $limit Maximum number of occurrences to generate
     * @return array<DateTimeImmutable>
     */
    protected function getSabreOccurrences(
        string $rruleString,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): array {
        // Create a minimal VEVENT with the RRULE
        $vcalendar = new VCalendar();
        $vevent = $vcalendar->add('VEVENT');
        $vevent->add('DTSTART', $start);
        $vevent->add('RRULE', $rruleString);
        $vevent->add('SUMMARY', 'Compatibility Test Event');
        $vevent->add('UID', 'compatibility-test-'.uniqid());

        // Use sabre/vobject's EventIterator to expand the RRULE
        $iterator = new EventIterator($vevent);

        $occurrences = [];
        $count = 0;

        foreach ($iterator as $occurrence) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            // Get the DTSTART from the iterator directly
            $occurrenceDate = $iterator->getDtStart();

            // Convert DateTime to DateTimeImmutable if needed
            if ($occurrenceDate instanceof \DateTime) {
                $occurrences[] = DateTimeImmutable::createFromMutable($occurrenceDate);
            } else {
                $occurrences[] = $occurrenceDate;
            }

            ++$count;

            // Safety break for infinite sequences
            if ($count > 1000) {
                break;
            }
        }

        return $occurrences;
    }

    /**
     * Compare occurrence arrays and assert they are identical.
     *
     * @param array<DateTimeImmutable> $rrulerOccurrences
     * @param array<DateTimeImmutable> $sabreOccurrences
     * @param string $rruleString The RRULE being tested (for error messages)
     * @param string $testDescription Description of the test case
     */
    protected function assertOccurrencesMatch(
        array $rrulerOccurrences,
        array $sabreOccurrences,
        string $rruleString,
        string $testDescription = '',
    ): void {
        $this->assertCount(
            count($sabreOccurrences),
            $rrulerOccurrences,
            "Occurrence count mismatch for RRULE '{$rruleString}'".
            ($testDescription ? " ({$testDescription})" : '')
        );

        foreach ($sabreOccurrences as $index => $sabreOccurrence) {
            $this->assertTrue(
                isset($rrulerOccurrences[$index]),
                "Missing occurrence at index {$index} for RRULE '{$rruleString}'".
                ($testDescription ? " ({$testDescription})" : '')
            );

            $this->assertEquals(
                $sabreOccurrence,
                $rrulerOccurrences[$index],
                "Occurrence mismatch at index {$index} for RRULE '{$rruleString}'".
                ($testDescription ? " ({$testDescription})" : '').
                ". Expected: {$sabreOccurrence->format('Y-m-d H:i:s')}, ".
                "Got: {$rrulerOccurrences[$index]->format('Y-m-d H:i:s')}"
            );
        }
    }

    /**
     * Test RRULE compatibility between Rruler and sabre/vobject.
     *
     * @param string $rruleString The RRULE string to test
     * @param DateTimeImmutable $start The start date
     * @param int $limit Number of occurrences to compare
     * @param string $testDescription Optional description for the test
     */
    protected function assertRruleCompatibility(
        string $rruleString,
        DateTimeImmutable $start,
        int $limit = 10,
        string $testDescription = '',
    ): void {
        $rrulerOccurrences = $this->getRrulerOccurrences($rruleString, $start, $limit);
        $sabreOccurrences = $this->getSabreOccurrences($rruleString, $start, $limit);

        $this->assertOccurrencesMatch(
            $rrulerOccurrences,
            $sabreOccurrences,
            $rruleString,
            $testDescription
        );
    }

    /**
     * Format occurrences for debugging output.
     *
     * @param array<DateTimeImmutable> $occurrences
     */
    protected function formatOccurrences(array $occurrences): string
    {
        return '['.implode(', ', array_map(
            fn (DateTimeImmutable $dt) => $dt->format('Y-m-d H:i:s'),
            $occurrences
        )).']';
    }

    /**
     * Assert compatibility with python-dateutil using generated fixture data.
     *
     * This method compares Rruler's output against pre-generated occurrences
     * from python-dateutil library, providing an additional validation layer
     * alongside the existing sabre/vobject compatibility tests.
     *
     * @param string $fixtureName Name of the fixture file (without .yaml extension)
     * @param string $testDescription Optional description for the test
     * @param array<string> $groups Optional PHPUnit groups to apply
     */
    protected function assertPythonDateutilFixtureCompatibility(
        string $fixtureName,
        string $testDescription = '',
        array $groups = [],
    ): void {
        // Skip if fixture loader is not available
        if ($this->pythonDateutilFixtureLoader === null) {
            $this->markTestSkipped('Python-dateutil fixtures not available');
        }

        // Skip if groups are specified and current test doesn't match
        if (!empty($groups) && !$this->shouldRunPythonDateutilValidation($groups)) {
            $this->markTestSkipped('Python-dateutil validation disabled for this group');
        }

        // Load all fixtures and find the requested one
        // For multi-test fixtures that were converted from legacy format,
        // we need to look for the expanded fixture name with _0 suffix
        try {
            $allFixtures = $this->getPreloadedFixtures();

            // First try exact match
            if (isset($allFixtures[$fixtureName])) {
                $fixture = $allFixtures[$fixtureName];
            }
            // Then try with _0 suffix for converted single-test fixtures
            elseif (isset($allFixtures[$fixtureName.'_0'])) {
                $fixture = $allFixtures[$fixtureName.'_0'];
            } else {
                throw new \RuntimeException("Fixture {$fixtureName} not found");
            }
        } catch (\RuntimeException $e) {
            $this->markTestSkipped("Fixture {$fixtureName} not available: ".$e->getMessage());
        }

        // Verify fixture integrity
        if (!$this->pythonDateutilFixtureLoader->verifyFixtureIntegrity($fixture)) {
            $this->fail("Fixture {$fixtureName} failed integrity check");
        }

        // Extract test data from fixture
        $inputData = $fixture['input'];
        $expectedOccurrences = $fixture['expected_occurrences'];
        $metadata = $fixture['metadata'];

        // Parse input parameters
        $rruleString = $inputData['rrule'];
        $dtstart = new DateTimeImmutable($inputData['dtstart']);
        $timezone = $inputData['timezone'] ?? 'UTC';

        // Set timezone if specified
        if ($timezone !== 'UTC') {
            $tz = new \DateTimeZone($timezone);
            $dtstart = $dtstart->setTimezone($tz);
        }

        // Generate occurrences using Rruler (pure RRULE semantics for python-dateutil compatibility)
        // Note: Unlike sabre/vobject compatibility testing, python-dateutil follows pure RRULE
        // semantics where the start date is only included if it matches the pattern
        $rrule = $this->rruler->parse($rruleString);
        $rrulerOccurrences = iterator_to_array(
            $this->occurrenceGenerator->generateOccurrences($rrule, $dtstart, count($expectedOccurrences))
        );

        // Compare with expected python-dateutil results
        try {
            $this->assertPythonDateutilOccurrencesMatch(
                $rrulerOccurrences,
                $expectedOccurrences,
                $rruleString,
                $fixtureName,
                $testDescription,
                $metadata
            );
        } catch (\Exception $e) {
            $this->handleFixtureValidationError($e, $fixtureName, $rruleString, $testDescription, $metadata);
            throw $e; // Re-throw after logging
        }
    }

    /**
     * Compare Rruler occurrences with python-dateutil expected results.
     *
     * @param array<DateTimeImmutable> $rrulerOccurrences
     * @param array<string> $expectedOccurrences
     */
    protected function assertPythonDateutilOccurrencesMatch(
        array $rrulerOccurrences,
        array $expectedOccurrences,
        string $rruleString,
        string $fixtureName,
        string $testDescription = '',
        array $metadata = [],
    ): void {
        $pythonDateutilVersion = $metadata['python_dateutil_version'] ?? 'unknown';

        $this->assertCount(
            count($expectedOccurrences),
            $rrulerOccurrences,
            "Occurrence count mismatch for fixture '{$fixtureName}' (RRULE: '{$rruleString}', python-dateutil: {$pythonDateutilVersion})".
            ($testDescription ? " ({$testDescription})" : '')
        );

        foreach ($expectedOccurrences as $index => $expectedOccurrenceString) {
            $this->assertTrue(
                isset($rrulerOccurrences[$index]),
                "Missing occurrence at index {$index} for fixture '{$fixtureName}' (python-dateutil: {$pythonDateutilVersion})".
                ($testDescription ? " ({$testDescription})" : '')
            );

            // Parse expected occurrence string
            $expectedOccurrence = new DateTimeImmutable($expectedOccurrenceString);
            $actualOccurrence = $rrulerOccurrences[$index];

            // Compare with tolerance for timezone differences
            $this->assertEquals(
                $expectedOccurrence->format('Y-m-d H:i:s'),
                $actualOccurrence->format('Y-m-d H:i:s'),
                "Occurrence mismatch at index {$index} for fixture '{$fixtureName}' ".
                "(RRULE: '{$rruleString}', python-dateutil: {$pythonDateutilVersion})".
                ($testDescription ? " ({$testDescription})" : '').
                ". Expected: {$expectedOccurrence->format('Y-m-d H:i:s')}, ".
                "Got: {$actualOccurrence->format('Y-m-d H:i:s')}"
            );
        }
    }

    /**
     * Check if python-dateutil validation should run based on groups or environment.
     *
     * @param array<string> $groups
     */
    protected function shouldRunPythonDateutilValidation(array $groups = []): bool
    {
        // Check environment variable for enabling/disabling validation
        $envEnabled = getenv('PYTHON_DATEUTIL_VALIDATION');
        if ($envEnabled !== false) {
            return filter_var($envEnabled, FILTER_VALIDATE_BOOLEAN);
        }

        // Check for specific groups
        if (!empty($groups)) {
            // For now, always run if groups are specified
            // In a real implementation, you might check PHPUnit's current test groups
            return true;
        }

        // Default: run if fixtures are available
        return $this->pythonDateutilFixtureLoader !== null;
    }

    /**
     * Load python-dateutil fixtures by category for batch testing.
     *
     * @param string $category Category to filter by
     * @return array<string, array> Fixture data keyed by fixture name
     */
    protected function loadPythonDateutilFixturesByCategory(string $category): array
    {
        if ($this->pythonDateutilFixtureLoader === null) {
            return [];
        }

        return $this->pythonDateutilFixtureLoader->loadFixturesByCategory($category);
    }

    /**
     * Get available python-dateutil fixture categories.
     *
     * @return array<string> Array of categories
     */
    protected function getPythonDateutilFixtureCategories(): array
    {
        if ($this->pythonDateutilFixtureLoader === null) {
            return [];
        }

        return $this->pythonDateutilFixtureLoader->getAvailableCategories();
    }

    /**
     * Create a data provider from python-dateutil fixtures for a specific category.
     *
     * @param string $category Category to filter by
     * @return array<string, array> PHPUnit data provider format
     */
    protected function createPythonDateutilDataProvider(string $category): array
    {
        if ($this->pythonDateutilFixtureLoader === null) {
            return [];
        }

        $fixtures = $this->pythonDateutilFixtureLoader->loadFixturesByCategory($category);

        return $this->pythonDateutilFixtureLoader->convertToDataProvider($fixtures);
    }

    /**
     * Get preloaded fixtures with batch optimization.
     *
     * This method implements batch loading to minimize file I/O during test execution.
     * Fixtures are loaded once and cached for the duration of the test run.
     *
     * @return array<string, array<string, mixed>> Array of all fixtures
     */
    private function getPreloadedFixtures(): array
    {
        $cacheKey = 'batch_fixtures_'.spl_object_hash($this->pythonDateutilFixtureLoader);

        if (!isset(self::$preloadedFixtures[$cacheKey])) {
            // Load all fixtures once and cache them for the test session
            self::$preloadedFixtures[$cacheKey] = $this->pythonDateutilFixtureLoader->loadAllFixtures();
        }

        return self::$preloadedFixtures[$cacheKey];
    }

    /**
     * Preload fixtures for better performance in batch test scenarios.
     *
     * This method can be called in setUpBeforeClass() to preload all fixtures
     * before any tests run, further optimizing batch test performance.
     */
    public static function preloadFixtures(): void
    {
        // This method can be overridden in test classes to preload specific fixtures
        // For now, it's a placeholder for future enhancement
    }

    /**
     * Clear preloaded fixtures cache (useful for memory management in long test runs).
     */
    public static function clearPreloadedFixtures(): void
    {
        self::$preloadedFixtures = [];
    }

    /**
     * Get batch loading statistics for performance monitoring.
     *
     * @return array<string, mixed> Array containing loading statistics
     */
    public static function getBatchLoadingStats(): array
    {
        return [
            'preloaded_fixture_sets' => count(self::$preloadedFixtures),
            'memory_usage_bytes' => strlen(serialize(self::$preloadedFixtures)),
        ];
    }

    /**
     * Handle fixture validation errors with comprehensive logging and diagnostics.
     *
     * @param \Exception $exception The original exception
     * @param string $fixtureName Name of the fixture that failed
     * @param string $rruleString The RRULE being tested
     * @param string $testDescription Test description
     * @param array<string, mixed> $metadata Fixture metadata
     */
    private function handleFixtureValidationError(
        \Exception $exception,
        string $fixtureName,
        string $rruleString,
        string $testDescription,
        array $metadata,
    ): void {
        // Log detailed error information for debugging
        $errorDetails = [
            'fixture_name' => $fixtureName,
            'rrule' => $rruleString,
            'test_description' => $testDescription,
            'python_dateutil_version' => $metadata['python_dateutil_version'] ?? 'unknown',
            'error_type' => get_class($exception),
            'error_message' => $exception->getMessage(),
            'timestamp' => date('Y-m-d H:i:s'),
        ];

        // Log to PHP error log for investigation
        error_log('Fixture validation error: '.json_encode($errorDetails, JSON_PRETTY_PRINT));

        // Check for specific error types and provide helpful diagnostics
        if (strpos($exception->getMessage(), 'Occurrence mismatch') !== false) {
            $this->diagnoseOccurrenceMismatch($fixtureName, $rruleString, $metadata);
        } elseif (strpos($exception->getMessage(), 'hash mismatch') !== false) {
            $this->diagnoseHashMismatch($fixtureName, $metadata);
        }
    }

    /**
     * Diagnose occurrence mismatch errors.
     *
     * @param string $fixtureName Name of the fixture
     * @param string $rruleString The RRULE being tested
     * @param array<string, mixed> $metadata Fixture metadata
     */
    private function diagnoseOccurrenceMismatch(string $fixtureName, string $rruleString, array $metadata): void
    {
        $diagnostic = [
            'issue' => 'Occurrence mismatch between Rruler and python-dateutil',
            'fixture' => $fixtureName,
            'rrule' => $rruleString,
            'possible_causes' => [
                'Start date handling differences (iCalendar vs pure RRULE semantics)',
                'Edge case interpretation differences',
                'Timezone handling variations',
                'Complex pattern implementation differences',
            ],
            'investigation_steps' => [
                'Check if start date matches the RRULE pattern',
                'Verify timezone handling is consistent',
                'Compare individual occurrence generation step by step',
                'Check for edge cases in the specific RRULE pattern',
            ],
            'python_dateutil_version' => $metadata['python_dateutil_version'] ?? 'unknown',
        ];

        error_log('Occurrence mismatch diagnostic: '.json_encode($diagnostic, JSON_PRETTY_PRINT));
    }

    /**
     * Diagnose hash mismatch errors.
     *
     * @param string $fixtureName Name of the fixture
     * @param array<string, mixed> $metadata Fixture metadata
     */
    private function diagnoseHashMismatch(string $fixtureName, array $metadata): void
    {
        $diagnostic = [
            'issue' => 'Hash mismatch indicates fixture integrity problem',
            'fixture' => $fixtureName,
            'possible_causes' => [
                'Fixture file was modified after generation',
                'Different python-dateutil version generated different results',
                'Hash calculation method differences between Python and PHP',
                'File corruption or encoding issues',
            ],
            'recommended_actions' => [
                'Regenerate fixtures using the python-dateutil script',
                'Verify python-dateutil version consistency',
                'Check file permissions and integrity',
                'Compare with backup fixtures if available',
            ],
            'metadata_hash' => $metadata['input_hash'] ?? 'not found',
            'python_dateutil_version' => $metadata['python_dateutil_version'] ?? 'unknown',
        ];

        error_log('Hash mismatch diagnostic: '.json_encode($diagnostic, JSON_PRETTY_PRINT));
    }

    /**
     * Validate fixture integrity with enhanced error handling.
     *
     * @param array<string, mixed> $fixture Fixture data to validate
     * @param string $fixtureName Name of the fixture for error reporting
     * @return bool True if fixture is valid
     */
    private function validateFixtureIntegrity(array $fixture, string $fixtureName): bool
    {
        try {
            // Check required structure
            if (!isset($fixture['input']) || !is_array($fixture['input'])) {
                throw new \RuntimeException("Fixture {$fixtureName} missing 'input' section");
            }

            if (!isset($fixture['expected_occurrences']) || !is_array($fixture['expected_occurrences'])) {
                throw new \RuntimeException("Fixture {$fixtureName} missing 'expected_occurrences' section");
            }

            if (!isset($fixture['metadata']) || !is_array($fixture['metadata'])) {
                throw new \RuntimeException("Fixture {$fixtureName} missing 'metadata' section");
            }

            // Validate critical fields
            $input = $fixture['input'];
            if (!isset($input['rrule']) || !is_string($input['rrule'])) {
                throw new \RuntimeException("Fixture {$fixtureName} missing or invalid 'rrule' field");
            }

            if (!isset($input['dtstart']) || !is_string($input['dtstart'])) {
                throw new \RuntimeException("Fixture {$fixtureName} missing or invalid 'dtstart' field");
            }

            if (empty($fixture['expected_occurrences'])) {
                throw new \RuntimeException("Fixture {$fixtureName} has empty 'expected_occurrences'");
            }

            return true;
        } catch (\Exception $e) {
            $this->handleFixtureValidationError($e, $fixtureName, $input['rrule'] ?? 'unknown', '', $fixture['metadata'] ?? []);

            return false;
        }
    }
}
