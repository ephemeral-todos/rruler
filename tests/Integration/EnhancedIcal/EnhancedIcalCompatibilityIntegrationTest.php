<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\EnhancedIcal;

use EphemeralTodos\Rruler\Testing\Utilities\EnhancedCompatibilityReportGenerator;
use EphemeralTodos\Rruler\Testing\Utilities\EnhancedIcalCompatibilityFramework;
use PHPUnit\Framework\TestCase;

final class EnhancedIcalCompatibilityIntegrationTest extends TestCase
{
    private EnhancedIcalCompatibilityFramework $compatibilityFramework;
    private EnhancedCompatibilityReportGenerator $reportGenerator;
    private string $testDataPath;

    protected function setUp(): void
    {
        $this->compatibilityFramework = new EnhancedIcalCompatibilityFramework();
        $this->reportGenerator = new EnhancedCompatibilityReportGenerator();
        $this->testDataPath = dirname(__DIR__, 2).'/data/enhanced-ical';
    }

    public function testSyntheticComplexMixedComponents(): void
    {
        $testFile = $this->testDataPath.'/synthetic/complex-mixed-components.ics';
        $this->assertFileExists($testFile, 'Synthetic test file should exist');

        $icalContent = file_get_contents($testFile);
        $this->assertNotFalse($icalContent, 'Should be able to read test file');

        $results = $this->compatibilityFramework->compareParsingResults($icalContent);

        // Basic structure assertions
        $this->assertArrayHasKey('rruler_results', $results);
        $this->assertArrayHasKey('sabre_results', $results);
        $this->assertArrayHasKey('differences', $results);
        $this->assertArrayHasKey('similarities', $results);

        // Both libraries should successfully parse the file
        $this->assertEmpty($results['parsing_errors'], 'No parsing errors should occur for synthetic test data');

        // Should find components in both results
        $this->assertNotEmpty($results['rruler_results'], 'Rruler should find components');
        $this->assertNotEmpty($results['sabre_results'], 'sabre/vobject should find components');
    }

    public function testSyntheticEdgeCaseDateFormats(): void
    {
        $testFile = $this->testDataPath.'/synthetic/edge-case-date-formats.ics';
        $this->assertFileExists($testFile, 'Edge case test file should exist');

        $icalContent = file_get_contents($testFile);
        $results = $this->compatibilityFramework->compareParsingResults($icalContent);

        // Should handle edge cases gracefully
        $this->assertArrayHasKey('rruler_results', $results);
        $this->assertArrayHasKey('sabre_results', $results);

        // May have parsing errors due to malformed dates, but should not crash
        if (empty($results['parsing_errors'])) {
            $this->assertNotEmpty($results['rruler_results'], 'Rruler should parse valid components');
            $this->assertNotEmpty($results['sabre_results'], 'sabre/vobject should parse valid components');
        }
    }

    public function testLargeMultiComponentFile(): void
    {
        $testFile = $this->testDataPath.'/synthetic/large-multi-component.ics';
        $this->assertFileExists($testFile, 'Large multi-component test file should exist');

        $icalContent = file_get_contents($testFile);
        $results = $this->compatibilityFramework->compareParsingResults($icalContent);

        $this->assertEmpty($results['parsing_errors'], 'No parsing errors for large multi-component file');

        // Should find multiple components
        $this->assertGreaterThanOrEqual(10, count($results['rruler_results']), 'Should find at least 10 components in Rruler');
        $this->assertGreaterThanOrEqual(10, count($results['sabre_results']), 'Should find at least 10 components in sabre/vobject');

        // Component counts should be similar
        $componentCountDiff = abs(count($results['rruler_results']) - count($results['sabre_results']));
        $this->assertLessThanOrEqual(2, $componentCountDiff, 'Component counts should be very similar');
    }

    public function testMicrosoftOutlookSampleFile(): void
    {
        $testFiles = glob($this->testDataPath.'/microsoft-outlook/*.ics');
        $this->assertNotEmpty($testFiles, 'Microsoft Outlook test files should exist');

        $testFile = $testFiles[0];
        $icalContent = file_get_contents($testFile);
        $results = $this->compatibilityFramework->compareParsingResults($icalContent);

        $this->assertArrayHasKey('rruler_results', $results);
        $this->assertArrayHasKey('sabre_results', $results);

        // Should handle Outlook-specific formatting
        if (empty($results['parsing_errors'])) {
            $this->assertNotEmpty($results['rruler_results'], 'Should parse Outlook components');
            $this->assertNotEmpty($results['sabre_results'], 'sabre/vobject should parse Outlook components');
        }
    }

    public function testOccurrenceGenerationCompatibility(): void
    {
        $testFile = $this->testDataPath.'/synthetic/complex-mixed-components.ics';
        $icalContent = file_get_contents($testFile);

        $results = $this->compatibilityFramework->compareOccurrenceGeneration($icalContent, 20);

        $this->assertArrayHasKey('rruler_occurrences', $results);
        $this->assertArrayHasKey('sabre_occurrences', $results);
        $this->assertArrayHasKey('occurrence_similarities', $results);
        $this->assertArrayHasKey('occurrence_differences', $results);

        // Should generate occurrences without major errors
        if (empty($results['generation_errors'])) {
            $this->assertNotEmpty($results['rruler_occurrences'], 'Rruler should generate occurrences');
            $this->assertNotEmpty($results['sabre_occurrences'], 'sabre/vobject should generate occurrences');
        }
    }

    public function testCompatibilityReportGeneration(): void
    {
        $testFile = $this->testDataPath.'/synthetic/complex-mixed-components.ics';
        $icalContent = file_get_contents($testFile);

        $parsingResults = $this->compatibilityFramework->compareParsingResults($icalContent);
        $occurrenceResults = $this->compatibilityFramework->compareOccurrenceGeneration($icalContent, 20);

        $comparisonResults = [$parsingResults, $occurrenceResults];
        $reportPath = $this->reportGenerator->generateReport($comparisonResults, 'test-compatibility-report');

        $this->assertFileExists($reportPath, 'Compatibility report should be generated');

        $reportContent = file_get_contents($reportPath);
        $this->assertStringContainsString('# Enhanced iCalendar Compatibility Report', $reportContent);
        $this->assertStringContainsString('## Executive Summary', $reportContent);
        $this->assertStringContainsString('## Parsing Comparison Results', $reportContent);

        // Clean up test report
        if (file_exists($reportPath)) {
            unlink($reportPath);
        }
    }

    public function testMultipleApplicationFormatsCompatibility(): void
    {
        $testDirectories = [
            'synthetic',
            'microsoft-outlook',
            'google-calendar',
            'apple-calendar',
        ];

        $allResults = [];
        foreach ($testDirectories as $directory) {
            $testFiles = glob($this->testDataPath.'/'.$directory.'/*.ics');
            if (empty($testFiles)) {
                continue;
            }

            $testFile = $testFiles[0];
            $icalContent = file_get_contents($testFile);
            $results = $this->compatibilityFramework->compareParsingResults($icalContent);
            $allResults[$directory] = $results;
        }

        $this->assertNotEmpty($allResults, 'Should test multiple application formats');
        $this->assertGreaterThanOrEqual(3, count($allResults), 'Should test at least 3 different formats');

        // Verify each format produces results
        foreach ($allResults as $source => $results) {
            $this->assertArrayHasKey('rruler_results', $results, "Should have Rruler results for {$source}");
            $this->assertArrayHasKey('sabre_results', $results, "Should have sabre results for {$source}");
        }
    }

    public function testCompatibilityMetricsCalculation(): void
    {
        $testFile = $this->testDataPath.'/synthetic/complex-mixed-components.ics';
        $icalContent = file_get_contents($testFile);

        $results = $this->compatibilityFramework->compareParsingResults($icalContent);

        if (!empty($results['similarities'])) {
            $similarities = $results['similarities'];

            $this->assertArrayHasKey('component_match_percentage', $similarities);
            $this->assertArrayHasKey('field_match_percentage', $similarities);
            $this->assertArrayHasKey('matching_components', $similarities);
            $this->assertArrayHasKey('total_components', $similarities);

            // Percentages should be between 0 and 100
            $this->assertGreaterThanOrEqual(0, $similarities['component_match_percentage']);
            $this->assertLessThanOrEqual(100, $similarities['component_match_percentage']);
            $this->assertGreaterThanOrEqual(0, $similarities['field_match_percentage']);
            $this->assertLessThanOrEqual(100, $similarities['field_match_percentage']);

            // Matching components should not exceed total
            $this->assertLessThanOrEqual($similarities['total_components'], $similarities['matching_components']);
        }
    }
}
