<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Testing;

use PHPUnit\Framework\TestCase;

/**
 * Tests that validate the organization of testing utilities and autoloading.
 *
 * This test ensures that utility classes are properly located and accessible
 * from their expected locations after reorganization.
 */
class TestingUtilityOrganizationTest extends TestCase
{
    public function testYamlFixtureLoaderIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Fixtures\\YamlFixtureLoader';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('YamlFixtureLoader should be accessible at %s', $expectedClass)
        );
    }

    public function testRrulePatternGeneratorIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Utilities\\RrulePatternGenerator';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('RrulePatternGenerator should be accessible at %s', $expectedClass)
        );
    }

    public function testEnhancedCompatibilityReportGeneratorIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Utilities\\EnhancedCompatibilityReportGenerator';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('EnhancedCompatibilityReportGenerator should be accessible at %s', $expectedClass)
        );
    }

    public function testEnhancedIcalCompatibilityFrameworkIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Utilities\\EnhancedIcalCompatibilityFramework';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('EnhancedIcalCompatibilityFramework should be accessible at %s', $expectedClass)
        );
    }

    public function testHybridCompatibilityReporterIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Reporting\\HybridCompatibilityReporter';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('HybridCompatibilityReporter should be accessible at %s', $expectedClass)
        );
    }

    public function testResultComparatorIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\Utilities\\ResultComparator';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('ResultComparator should be accessible at %s', $expectedClass)
        );
    }

    public function testCompatibilityTestCaseIsAccessibleFromSrcLocation(): void
    {
        $expectedClass = 'EphemeralTodos\\Rruler\\Testing\\TestCase\\CompatibilityTestCase';

        $this->assertTrue(
            class_exists($expectedClass),
            sprintf('CompatibilityTestCase should be accessible at %s', $expectedClass)
        );
    }

    public function testAutoloadingConfigurationForSrcTesting(): void
    {
        // Verify that the src/Testing namespace will be autoloaded correctly
        $composerData = json_decode(file_get_contents(__DIR__.'/../../../composer.json'), true);

        $this->assertArrayHasKey('autoload', $composerData);
        $this->assertArrayHasKey('psr-4', $composerData['autoload']);
        $this->assertArrayHasKey('EphemeralTodos\\Rruler\\', $composerData['autoload']['psr-4']);
        $this->assertEquals('src/', $composerData['autoload']['psr-4']['EphemeralTodos\\Rruler\\']);

        // This ensures that classes under src/Testing/ will be autoloaded as part of the main namespace
    }

    public function testTestingDirectoryStructureExists(): void
    {
        $srcTestingPath = __DIR__.'/../../../src/Testing';

        $this->assertDirectoryExists($srcTestingPath, 'src/Testing directory should exist');

        // Check for expected subdirectories
        $expectedSubdirs = ['Fixtures', 'Utilities', 'Reporting'];

        foreach ($expectedSubdirs as $subdir) {
            $subdirPath = $srcTestingPath.'/'.$subdir;
            $this->assertDirectoryExists($subdirPath, sprintf('src/Testing/%s directory should exist', $subdir));
        }
    }

    public function testTestingBehaviorDirectoryStructureExists(): void
    {
        $srcTestingBehaviorPath = __DIR__.'/../../../src/Testing/Behavior';

        $this->assertDirectoryExists($srcTestingBehaviorPath, 'src/Testing/Behavior directory should exist');

        // Verify that behavior test utilities are in place
        $expectedFiles = [
            'TestOccurrenceGenerationBehavior.php',
            'TestRrulerBehavior.php',
        ];

        foreach ($expectedFiles as $file) {
            $filePath = $srcTestingBehaviorPath.'/'.$file;
            $this->assertFileExists($filePath, sprintf('Behavior test utility %s should exist', $file));
        }
    }
}
