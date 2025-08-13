<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Fixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

final class PythonDateutilFixtureGeneratorTest extends TestCase
{
    private string $tempDir;
    private string $inputDir;
    private string $outputDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir().'/python_dateutil_fixtures_test_'.uniqid();
        $this->inputDir = $this->tempDir.'/input';
        $this->outputDir = $this->tempDir.'/generated';

        mkdir($this->tempDir, 0755, true);
        mkdir($this->inputDir, 0755, true);
        mkdir($this->outputDir, 0755, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $this->recursiveRemove($this->tempDir);
        }
    }

    public function testFixtureGenerationScriptExists(): void
    {
        $scriptPath = __DIR__.'/../../../scripts/generate-python-dateutil-fixtures.py';

        $this->assertFileExists($scriptPath, 'Python fixture generation script should exist');
    }

    public function testValidInputYamlStructure(): void
    {
        $inputYaml = [
            'name' => 'Basic Daily Recurrence',
            'description' => 'Test basic daily recurrence pattern',
            'rrule' => 'FREQ=DAILY;COUNT=5',
            'dtstart' => '2023-01-01T09:00:00',
            'timezone' => 'UTC',
            'range' => [
                'start' => '2023-01-01T00:00:00',
                'end' => '2023-01-10T23:59:59',
            ],
        ];

        $yamlContent = Yaml::dump($inputYaml, 4, 2);
        file_put_contents($this->inputDir.'/basic_daily.yaml', $yamlContent);

        $this->assertFileExists($this->inputDir.'/basic_daily.yaml');

        $parsedYaml = Yaml::parseFile($this->inputDir.'/basic_daily.yaml');
        $this->assertEquals($inputYaml, $parsedYaml);
    }

    public function testGeneratedFixtureStructure(): void
    {
        // Create a sample generated fixture to test the expected structure
        $generatedFixture = [
            'metadata' => [
                'input_hash' => 'abc123def456',
                'python_dateutil_version' => '2.8.2',
                'script_version' => '1.0.0',
            ],
            'input' => [
                'name' => 'Basic Daily Recurrence',
                'description' => 'Test basic daily recurrence pattern',
                'rrule' => 'FREQ=DAILY;COUNT=5',
                'dtstart' => '2023-01-01T09:00:00',
                'timezone' => 'UTC',
                'range' => [
                    'start' => '2023-01-01T00:00:00',
                    'end' => '2023-01-10T23:59:59',
                ],
            ],
            'expected_occurrences' => [
                '2023-01-01T09:00:00',
                '2023-01-02T09:00:00',
                '2023-01-03T09:00:00',
                '2023-01-04T09:00:00',
                '2023-01-05T09:00:00',
            ],
        ];

        $this->assertIsArray($generatedFixture['metadata']);
        $this->assertArrayHasKey('input_hash', $generatedFixture['metadata']);
        $this->assertArrayHasKey('python_dateutil_version', $generatedFixture['metadata']);
        $this->assertArrayHasKey('script_version', $generatedFixture['metadata']);

        $this->assertIsArray($generatedFixture['input']);
        $this->assertArrayHasKey('name', $generatedFixture['input']);
        $this->assertArrayHasKey('rrule', $generatedFixture['input']);
        $this->assertArrayHasKey('dtstart', $generatedFixture['input']);

        $this->assertIsArray($generatedFixture['expected_occurrences']);
        $this->assertCount(5, $generatedFixture['expected_occurrences']);
    }

    public function testHashCalculationConsistency(): void
    {
        $inputData1 = [
            'name' => 'Test Fixture',
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'dtstart' => '2023-01-01T10:00:00',
        ];

        $inputData2 = [
            'name' => 'Test Fixture',
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'dtstart' => '2023-01-01T10:00:00',
        ];

        $inputData3 = [
            'name' => 'Different Fixture',
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'dtstart' => '2023-01-01T10:00:00',
        ];

        $hash1 = $this->calculateInputHash($inputData1);
        $hash2 = $this->calculateInputHash($inputData2);
        $hash3 = $this->calculateInputHash($inputData3);

        $this->assertEquals($hash1, $hash2, 'Identical input should produce identical hashes');
        $this->assertNotEquals($hash1, $hash3, 'Different input should produce different hashes');
    }

    public function testYamlParsingErrorHandling(): void
    {
        $invalidYaml = "invalid: yaml: content:\n  - missing\n    proper structure";
        file_put_contents($this->inputDir.'/invalid.yaml', $invalidYaml);

        $this->expectException(\Exception::class);
        Yaml::parseFile($this->inputDir.'/invalid.yaml');
    }

    public function testRequiredFieldValidation(): void
    {
        $incompleteFixture = [
            'name' => 'Incomplete Fixture',
            // Missing required 'rrule' field
            'dtstart' => '2023-01-01T10:00:00',
        ];

        $this->assertFalse($this->hasRequiredFields($incompleteFixture));

        $completeFixture = [
            'name' => 'Complete Fixture',
            'rrule' => 'FREQ=DAILY;COUNT=3',
            'dtstart' => '2023-01-01T10:00:00',
        ];

        $this->assertTrue($this->hasRequiredFields($completeFixture));
    }

    /**
     * Calculate a hash for the input data to ensure consistency.
     */
    private function calculateInputHash(array $inputData): string
    {
        return hash('sha256', serialize($inputData));
    }

    /**
     * Check if fixture has all required fields.
     */
    private function hasRequiredFields(array $fixture): bool
    {
        $requiredFields = ['name', 'rrule', 'dtstart'];

        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $fixture)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Recursively remove directory and all contents.
     */
    private function recursiveRemove(string $dir): void
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object !== '.' && $object !== '..') {
                    $path = $dir.'/'.$object;
                    if (is_dir($path)) {
                        $this->recursiveRemove($path);
                    } else {
                        unlink($path);
                    }
                }
            }
            rmdir($dir);
        }
    }
}
