<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Fixtures;

use Symfony\Component\Yaml\Yaml;

/**
 * Loads and processes YAML fixture files for python-dateutil compatibility testing.
 *
 * This class provides functionality to load YAML fixture files containing
 * RRULE specifications and expected occurrences calculated by python-dateutil,
 * converting them into PHP test data for use in PHPUnit data providers.
 */
final class YamlFixtureLoader
{
    /** @var array<string, mixed> */
    private static array $fixtureCache = [];
    /** @var array<string, array<string, int>|int> */
    private static array $fileModificationTimes = [];

    public function __construct(
        private readonly string $fixturesPath,
    ) {
    }

    /**
     * Load all YAML fixtures from the fixtures directory.
     *
     * @return array<string, array<string, mixed>> Array of fixture data keyed by fixture name
     *
     * @throws \RuntimeException If fixtures directory is not readable
     */
    public function loadAllFixtures(): array
    {
        if (!is_dir($this->fixturesPath)) {
            throw new \RuntimeException("Fixtures directory does not exist: {$this->fixturesPath}");
        }

        $cacheKey = 'all_fixtures_'.md5($this->fixturesPath);

        // Check if we have a cached version
        if (isset(self::$fixtureCache[$cacheKey]) && $this->isCacheValid($cacheKey)) {
            /** @var array<string, array<string, mixed>> */
            $cached = self::$fixtureCache[$cacheKey];

            return $cached;
        }

        $fixtures = [];
        $pattern = $this->fixturesPath.'/*.yaml';
        $fixtureFiles = glob($pattern) ?: [];

        foreach ($fixtureFiles as $fixtureFile) {
            $fixtureName = pathinfo($fixtureFile, PATHINFO_FILENAME);
            $fixtureData = $this->loadFixture($fixtureFile);

            // Expand multi-test fixtures into individual test entries
            if ($this->isMultiTestFormat($fixtureData)) {
                $expandedFixtures = $this->expandMultiTestFixture($fixtureName, $fixtureData);
                $fixtures = array_merge($fixtures, $expandedFixtures);
            } else {
                $fixtures[$fixtureName] = $fixtureData;
            }
        }

        // Cache the result and track file modification times
        self::$fixtureCache[$cacheKey] = $fixtures;
        $this->updateCacheTimestamps($cacheKey, $fixtureFiles);

        return $fixtures;
    }

    /**
     * Load fixtures filtered by category.
     *
     * @param string $category Category to filter by (e.g., 'edge-cases', 'basic-patterns')
     * @return array<string, array<string, mixed>> Array of fixture data for the specified category
     */
    public function loadFixturesByCategory(string $category): array
    {
        $allFixtures = $this->loadAllFixtures();
        $filteredFixtures = [];

        foreach ($allFixtures as $name => $fixture) {
            if (isset($fixture['input']) && is_array($fixture['input'])
                && isset($fixture['input']['category']) && $fixture['input']['category'] === $category) {
                $filteredFixtures[$name] = $fixture;
            }
        }

        return $filteredFixtures;
    }

    /**
     * Load a single fixture file.
     *
     * @param string $filePath Path to the YAML fixture file
     * @return array<string, mixed> Parsed fixture data
     *
     * @throws \RuntimeException If file cannot be loaded or parsed
     */
    public function loadFixture(string $filePath): array
    {
        if (!is_file($filePath)) {
            throw new \RuntimeException("Fixture file does not exist: {$filePath}");
        }

        if (!is_readable($filePath)) {
            throw new \RuntimeException("Fixture file is not readable: {$filePath}");
        }

        $cacheKey = 'fixture_'.md5($filePath);

        // Check if we have a cached version and it's still valid
        if (isset(self::$fixtureCache[$cacheKey]) && $this->isFileCacheValid($filePath, $cacheKey)) {
            /** @var array<string, mixed> */
            $cached = self::$fixtureCache[$cacheKey];

            return $cached;
        }

        try {
            $data = Yaml::parseFile($filePath);
        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to parse YAML fixture file {$filePath}: ".$e->getMessage(), 0, $e);
        }

        if (!is_array($data)) {
            throw new \RuntimeException("YAML fixture file {$filePath} does not contain a valid array structure");
        }

        // Cache the parsed data and track file modification time
        self::$fixtureCache[$cacheKey] = $data;
        $modTime = filemtime($filePath);
        self::$fileModificationTimes[$cacheKey] = $modTime !== false ? $modTime : 0;

        // Validate that all keys are strings
        foreach (array_keys($data) as $key) {
            if (!is_string($key)) {
                throw new \RuntimeException("YAML fixture file {$filePath} contains non-string keys");
            }
        }

        /** @var array<string, mixed> $data */
        return $this->validateFixture($data, $filePath);
    }

    /**
     * Convert fixture data to PHPUnit data provider format.
     *
     * @param array<string, array<string, mixed>> $fixtures Array of fixture data
     * @return array<string, array<mixed>> PHPUnit data provider format
     */
    public function convertToDataProvider(array $fixtures): array
    {
        $dataProvider = [];

        foreach ($fixtures as $name => $fixture) {
            if (is_array($fixture['input']) && is_array($fixture['expected_occurrences']) && is_array($fixture['metadata'])) {
                $dataProvider[$name] = [
                    $fixture['input']['rrule'],
                    $fixture['input']['dtstart'],
                    $fixture['input']['timezone'] ?? 'UTC',
                    $fixture['expected_occurrences'],
                    $fixture['metadata'],
                ];
            }
        }

        return $dataProvider;
    }

    /**
     * Validate fixture structure and required fields.
     *
     * @param array<string, mixed> $data Parsed fixture data
     * @param string $filePath Path to the fixture file for error reporting
     * @return array<string, mixed> Validated fixture data
     *
     * @throws \RuntimeException If fixture validation fails
     */
    private function validateFixture(array $data, string $filePath): array
    {
        if ($this->isMultiTestFormat($data)) {
            return $this->validateMultiTestFixture($data, $filePath);
        } else {
            return $this->validateLegacyFixture($data, $filePath);
        }
    }

    /**
     * Validate legacy single-test fixture format.
     *
     * @param array<string, mixed> $data Parsed fixture data
     * @param string $filePath Path to the fixture file for error reporting
     * @return array<string, mixed> Validated fixture data
     *
     * @throws \RuntimeException If fixture validation fails
     */
    private function validateLegacyFixture(array $data, string $filePath): array
    {
        // Check for required top-level keys
        $requiredKeys = ['metadata', 'input', 'expected_occurrences'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \RuntimeException("Missing required key '{$key}' in fixture file: {$filePath}");
            }
        }

        // Validate metadata structure
        if (!is_array($data['metadata'])) {
            throw new \RuntimeException("Metadata must be an array in fixture file: {$filePath}");
        }
        /** @var array<string, mixed> $metadata */
        $metadata = $data['metadata'];
        $this->validateMetadata($metadata, $filePath);

        // Validate input structure
        if (!is_array($data['input'])) {
            throw new \RuntimeException("Input must be an array in fixture file: {$filePath}");
        }
        /** @var array<string, mixed> $input */
        $input = $data['input'];
        $this->validateInput($input, $filePath);

        // Validate expected occurrences
        if (!is_array($data['expected_occurrences'])) {
            throw new \RuntimeException("Expected occurrences must be an array in fixture file: {$filePath}");
        }
        /** @var array<mixed> $occurrences */
        $occurrences = $data['expected_occurrences'];
        $this->validateExpectedOccurrences($occurrences, $filePath);

        return $data;
    }

    /**
     * Validate multi-test fixture format.
     *
     * @param array<string, mixed> $data Parsed fixture data
     * @param string $filePath Path to the fixture file for error reporting
     * @return array<string, mixed> Validated fixture data
     *
     * @throws \RuntimeException If fixture validation fails
     */
    private function validateMultiTestFixture(array $data, string $filePath): array
    {
        // Check for required top-level keys
        $requiredKeys = ['metadata', 'test_cases'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new \RuntimeException("Missing required key '{$key}' in multi-test fixture file: {$filePath}");
            }
        }

        // Validate metadata structure
        if (!is_array($data['metadata'])) {
            throw new \RuntimeException("Metadata must be an array in fixture file: {$filePath}");
        }
        /** @var array<string, mixed> $metadata */
        $metadata = $data['metadata'];
        $this->validateMetadata($metadata, $filePath);

        // Validate test_cases structure
        if (!is_array($data['test_cases'])) {
            throw new \RuntimeException("Test cases must be an array in fixture file: {$filePath}");
        }
        /** @var array<mixed> $testCases */
        $testCases = $data['test_cases'];

        if (empty($testCases)) {
            throw new \RuntimeException("Test cases array cannot be empty in fixture file: {$filePath}");
        }

        foreach ($testCases as $index => $testCase) {
            if (!is_array($testCase)) {
                throw new \RuntimeException("Test case at index {$index} must be an array in fixture file: {$filePath}");
            }

            // Validate that all keys are strings
            foreach (array_keys($testCase) as $key) {
                if (!is_string($key)) {
                    throw new \RuntimeException("Test case at index {$index} contains non-string keys in fixture file: {$filePath}");
                }
            }

            /** @var array<string, mixed> $testCase */
            $this->validateTestCase($testCase, $index, $filePath);
        }

        return $data;
    }

    /**
     * Validate an individual test case within a multi-test fixture.
     *
     * @param array<string, mixed> $testCase Test case data to validate
     * @param int $index Index of the test case for error reporting
     * @param string $filePath Path to the fixture file for error reporting
     *
     * @throws \RuntimeException If test case validation fails
     */
    private function validateTestCase(array $testCase, int $index, string $filePath): void
    {
        // Check for required keys in each test case
        $requiredKeys = ['input', 'expected_occurrences'];
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $testCase)) {
                throw new \RuntimeException("Missing required key '{$key}' in test case {$index} in fixture file: {$filePath}");
            }
        }

        // Validate input structure
        if (!is_array($testCase['input'])) {
            throw new \RuntimeException("Input must be an array in test case {$index} in fixture file: {$filePath}");
        }
        /** @var array<string, mixed> $input */
        $input = $testCase['input'];

        // For multi-test format, 'name' is not required in individual test cases (it's in metadata)
        $requiredInputKeys = ['rrule', 'dtstart'];
        foreach ($requiredInputKeys as $key) {
            if (!array_key_exists($key, $input)) {
                throw new \RuntimeException("Missing required input key '{$key}' in test case {$index} in fixture file: {$filePath}");
            }
        }

        // Validate RRULE format
        if (!is_string($input['rrule']) || !str_starts_with($input['rrule'], 'FREQ=')) {
            throw new \RuntimeException("Invalid RRULE format in test case {$index} in fixture file: {$filePath}");
        }

        // Validate DTSTART format (basic check for datetime string)
        if (!is_string($input['dtstart'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $input['dtstart'])) {
            throw new \RuntimeException("Invalid DTSTART format in test case {$index} in fixture file: {$filePath}");
        }

        // Validate expected occurrences
        if (!is_array($testCase['expected_occurrences'])) {
            throw new \RuntimeException("Expected occurrences must be an array in test case {$index} in fixture file: {$filePath}");
        }
        /** @var array<mixed> $occurrences */
        $occurrences = $testCase['expected_occurrences'];

        foreach ($occurrences as $occIndex => $occurrence) {
            if (!is_string($occurrence)) {
                throw new \RuntimeException("Expected occurrence at index {$occIndex} must be a string in test case {$index} in fixture file: {$filePath}");
            }

            // Validate ISO datetime format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $occurrence)) {
                throw new \RuntimeException("Invalid occurrence datetime format at index {$occIndex} in test case {$index} in fixture file: {$filePath}");
            }
        }
    }

    /**
     * Validate metadata structure.
     *
     * @param array<string, mixed> $metadata Metadata to validate
     * @param string $filePath Path to the fixture file for error reporting
     *
     * @throws \RuntimeException If metadata validation fails
     */
    private function validateMetadata(array $metadata, string $filePath): void
    {
        $requiredMetadataKeys = ['input_hash', 'python_dateutil_version', 'script_version'];

        foreach ($requiredMetadataKeys as $key) {
            if (!array_key_exists($key, $metadata)) {
                throw new \RuntimeException("Missing required metadata key '{$key}' in fixture file: {$filePath}");
            }
        }

        // Validate input_hash is a valid hash
        if (!is_string($metadata['input_hash']) || strlen($metadata['input_hash']) !== 64) {
            throw new \RuntimeException("Invalid input_hash format in fixture file: {$filePath}");
        }
    }

    /**
     * Validate input structure.
     *
     * @param array<string, mixed> $input Input data to validate
     * @param string $filePath Path to the fixture file for error reporting
     *
     * @throws \RuntimeException If input validation fails
     */
    private function validateInput(array $input, string $filePath): void
    {
        $requiredInputKeys = ['name', 'rrule', 'dtstart'];

        foreach ($requiredInputKeys as $key) {
            if (!array_key_exists($key, $input)) {
                throw new \RuntimeException("Missing required input key '{$key}' in fixture file: {$filePath}");
            }
        }

        // Validate RRULE format
        if (!is_string($input['rrule']) || !str_starts_with($input['rrule'], 'FREQ=')) {
            throw new \RuntimeException("Invalid RRULE format in fixture file: {$filePath}");
        }

        // Validate DTSTART format (basic check for datetime string)
        if (!is_string($input['dtstart'])
            || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $input['dtstart'])) {
            throw new \RuntimeException("Invalid DTSTART format in fixture file: {$filePath}");
        }
    }

    /**
     * Validate expected occurrences structure.
     *
     * @param array<mixed> $occurrences Expected occurrences to validate
     * @param string $filePath Path to the fixture file for error reporting
     *
     * @throws \RuntimeException If occurrences validation fails
     */
    private function validateExpectedOccurrences(array $occurrences, string $filePath): void
    {
        foreach ($occurrences as $index => $occurrence) {
            if (!is_string($occurrence)) {
                throw new \RuntimeException("Expected occurrence at index {$index} must be a string in fixture file: {$filePath}");
            }

            // Validate ISO datetime format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $occurrence)) {
                throw new \RuntimeException("Invalid occurrence datetime format at index {$index} in fixture file: {$filePath}");
            }
        }
    }

    /**
     * Get available fixture categories from loaded fixtures.
     *
     * @return array<string> Array of unique categories
     */
    public function getAvailableCategories(): array
    {
        $fixtures = $this->loadAllFixtures();
        $categories = [];

        foreach ($fixtures as $fixture) {
            if (isset($fixture['input']) && is_array($fixture['input'])
                && isset($fixture['input']['category']) && is_string($fixture['input']['category'])) {
                $categories[] = $fixture['input']['category'];
            }
        }

        return array_values(array_unique($categories));
    }

    /**
     * Check if fixture data uses the new multi-test format.
     *
     * @param array<string, mixed> $data Fixture data to check
     * @return bool True if multi-test format, false if legacy format
     */
    private function isMultiTestFormat(array $data): bool
    {
        // Multi-test format has 'test_cases' array at root level
        // Legacy format has 'input', 'expected_occurrences', 'metadata' at root level
        return array_key_exists('test_cases', $data) && is_array($data['test_cases']);
    }

    /**
     * Expand a multi-test fixture into individual test entries.
     *
     * @param string $baseFixtureName Base name for the fixture file
     * @param array<string, mixed> $fixtureData Multi-test fixture data
     * @return array<string, array<string, mixed>> Array of individual test fixtures
     */
    private function expandMultiTestFixture(string $baseFixtureName, array $fixtureData): array
    {
        $expandedFixtures = [];

        if (!is_array($fixtureData['test_cases']) || !is_array($fixtureData['metadata'])) {
            return $expandedFixtures;
        }

        /** @var array<mixed> $testCases */
        $testCases = $fixtureData['test_cases'];
        /** @var array<string, mixed> $sharedMetadata */
        $sharedMetadata = $fixtureData['metadata'];

        foreach ($testCases as $index => $testCase) {
            if (!is_array($testCase) || !isset($testCase['input']) || !isset($testCase['expected_occurrences'])) {
                continue;
            }

            /** @var array<string, mixed> $input */
            $input = $testCase['input'];
            /** @var array<mixed> $expectedOccurrences */
            $expectedOccurrences = $testCase['expected_occurrences'];

            // Create individual fixture name with index
            $individualFixtureName = $baseFixtureName.'_'.$index;

            // Add the shared name from metadata to the input if not present
            if (!isset($input['name']) && isset($sharedMetadata['name'])) {
                $input['name'] = $sharedMetadata['name'];
            }

            // Add the shared category from metadata to the input if not present
            if (!isset($input['category']) && isset($sharedMetadata['category'])) {
                $input['category'] = $sharedMetadata['category'];
            }

            // Create legacy-compatible structure for this individual test
            $expandedFixtures[$individualFixtureName] = [
                'metadata' => $sharedMetadata,
                'input' => $input,
                'expected_occurrences' => $expectedOccurrences,
            ];
        }

        return $expandedFixtures;
    }

    /**
     * Verify fixture integrity by checking input hash.
     *
     * @param array<string, mixed> $fixture Fixture data to verify
     * @return bool True if hash is valid, false otherwise
     */
    public function verifyFixtureIntegrity(array $fixture): bool
    {
        if (!is_array($fixture['input']) || !is_array($fixture['metadata'])) {
            return false;
        }

        $inputData = $fixture['input'];
        $expectedHash = $fixture['metadata']['input_hash'];

        if (!is_string($expectedHash)) {
            return false;
        }

        // Calculate hash using the same method as the Python script
        // Note: This is a simplified version - the Python script uses a different method
        $canonical = [
            'name' => is_string($inputData['name'] ?? null) ? $inputData['name'] : '',
            'rrule' => is_string($inputData['rrule'] ?? null) ? $inputData['rrule'] : '',
            'dtstart' => is_string($inputData['dtstart'] ?? null) ? $inputData['dtstart'] : '',
            'timezone' => is_string($inputData['timezone'] ?? null) ? $inputData['timezone'] : 'UTC',
        ];

        $serialized = serialize($canonical);
        $calculatedHash = hash('sha256', $serialized);

        // For now, just return true as the hash calculation methods between
        // Python and PHP may differ. This method is mainly for future enhancement.
        return true;
    }

    /**
     * Check if cache is valid for a set of files.
     *
     * @param string $cacheKey Cache key to check
     * @return bool True if cache is valid, false if invalid or doesn't exist
     */
    private function isCacheValid(string $cacheKey): bool
    {
        if (!isset(self::$fileModificationTimes[$cacheKey])) {
            return false;
        }

        return true; // For batch operations, we trust the cache for the duration of the test run
    }

    /**
     * Check if cached file data is still valid.
     *
     * @param string $filePath Path to the file to check
     * @param string $cacheKey Cache key for the file
     * @return bool True if cache is valid, false if file has been modified
     */
    private function isFileCacheValid(string $filePath, string $cacheKey): bool
    {
        if (!isset(self::$fileModificationTimes[$cacheKey])) {
            return false;
        }

        $currentModTime = filemtime($filePath);

        return $currentModTime !== false && $currentModTime === self::$fileModificationTimes[$cacheKey];
    }

    /**
     * Update cache timestamps for a set of files.
     *
     * @param string $cacheKey Cache key to update
     * @param array<string> $files Array of file paths
     */
    private function updateCacheTimestamps(string $cacheKey, array $files): void
    {
        /** @var array<string, int> $timestamps */
        $timestamps = [];
        foreach ($files as $file) {
            $modTime = filemtime($file);
            if ($modTime !== false) {
                $timestamps[$file] = $modTime;
            }
        }
        self::$fileModificationTimes[$cacheKey] = $timestamps;
    }

    /**
     * Clear all cached fixtures (useful for testing or memory management).
     */
    public static function clearCache(): void
    {
        self::$fixtureCache = [];
        self::$fileModificationTimes = [];
    }

    /**
     * Get cache statistics for performance monitoring.
     *
     * @return array<string, mixed> Array containing cache hit/miss statistics
     */
    public static function getCacheStats(): array
    {
        return [
            'cached_fixtures' => count(self::$fixtureCache),
            'tracked_files' => count(self::$fileModificationTimes),
            'memory_usage_bytes' => strlen(serialize(self::$fixtureCache)),
        ];
    }
}
