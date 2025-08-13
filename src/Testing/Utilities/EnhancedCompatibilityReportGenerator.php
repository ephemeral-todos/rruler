<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Utilities;

/**
 * Generate comprehensive compatibility reports between Rruler and sabre/vobject.
 */
final class EnhancedCompatibilityReportGenerator
{
    private string $reportDirectory;

    public function __construct(?string $reportDirectory = null)
    {
        $this->reportDirectory = $reportDirectory ?? dirname(__DIR__, 2).'/reports/enhanced-ical-compatibility';
        $this->ensureReportDirectoryExists();
    }

    /**
     * Generate a comprehensive compatibility report from comparison results.
     *
     * @param array<string, mixed> $comparisonResults
     */
    public function generateReport(array $comparisonResults, string $testName = 'enhanced-ical-compatibility'): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportContent = $this->generateReportHeader($testName, $timestamp);

        $reportContent .= $this->generateExecutiveSummary($comparisonResults);
        $reportContent .= $this->generateParsingComparisonSection($comparisonResults);
        $reportContent .= $this->generateOccurrenceComparisonSection($comparisonResults);
        $reportContent .= $this->generateDifferencesSection($comparisonResults);
        $reportContent .= $this->generateRecommendationsSection($comparisonResults);
        $reportContent .= $this->generateAppendixSection($comparisonResults);

        $reportFilename = $this->saveReport($reportContent, $testName, $timestamp);

        return $reportFilename;
    }

    /**
     * Generate a performance benchmark report.
     *
     * @param array<string, mixed> $benchmarkResults
     */
    public function generatePerformanceReport(array $benchmarkResults, string $testName = 'performance-benchmark'): string
    {
        $timestamp = date('Y-m-d H:i:s');
        $reportContent = $this->generateReportHeader($testName.' - Performance Analysis', $timestamp);

        $reportContent .= $this->generatePerformanceExecutiveSummary($benchmarkResults);
        $reportContent .= $this->generatePerformanceMetricsSection($benchmarkResults);
        $reportContent .= $this->generatePerformanceAnalysisSection($benchmarkResults);

        $reportFilename = $this->saveReport($reportContent, $testName.'-performance', $timestamp);

        return $reportFilename;
    }

    private function generateReportHeader(string $testName, string $timestamp): string
    {
        return <<<HEADER
# Enhanced iCalendar Compatibility Report

**Test Name:** {$testName}
**Generated:** {$timestamp}
**Libraries Compared:** Rruler vs sabre/vobject

---

HEADER;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateExecutiveSummary(array $results): string
    {
        $totalTests = count($results);
        $parsingSuccessCount = 0;
        $occurrenceSuccessCount = 0;
        $overallCompatibility = 0;

        foreach ($results as $result) {
            if (!is_array($result)) {
                continue;
            }
            if (empty($result['parsing_errors'])) {
                ++$parsingSuccessCount;
            }
            if (empty($result['generation_errors'])) {
                ++$occurrenceSuccessCount;
            }

            if (isset($result['similarities']['field_match_percentage']) && is_numeric($result['similarities']['field_match_percentage'])) {
                $overallCompatibility += (float) $result['similarities']['field_match_percentage'];
            }
        }

        $parsingSuccessRate = $totalTests > 0 ? round(($parsingSuccessCount / $totalTests) * 100, 2) : 0;
        $occurrenceSuccessRate = $totalTests > 0 ? round(($occurrenceSuccessCount / $totalTests) * 100, 2) : 0;
        $avgCompatibility = $totalTests > 0 ? round($overallCompatibility / $totalTests, 2) : 0;

        return <<<SUMMARY
## Executive Summary

### Key Metrics
- **Total Tests Executed:** {$totalTests}
- **Parsing Success Rate:** {$parsingSuccessRate}%
- **Occurrence Generation Success Rate:** {$occurrenceSuccessRate}%
- **Average Compatibility Score:** {$avgCompatibility}%

### Overall Assessment
The enhanced iCalendar compatibility testing reveals the current state of compatibility between 
Rruler and sabre/vobject for complex iCalendar parsing and occurrence generation scenarios.

---

SUMMARY;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateParsingComparisonSection(array $results): string
    {
        $section = "## Parsing Comparison Results\n\n";

        foreach ($results as $index => $result) {
            $section .= '### Test Case '.($index + 1)."\n\n";

            if (!empty($result['parsing_errors'])) {
                $section .= "**Parsing Errors:**\n";
                foreach ($result['parsing_errors'] as $library => $error) {
                    $section .= "- **{$library}:** {$error}\n";
                }
                $section .= "\n";
                continue;
            }

            if (isset($result['similarities'])) {
                $similarities = $result['similarities'];
                $section .= "**Compatibility Metrics:**\n";
                $section .= "- Component Match: {$similarities['component_match_percentage']}%\n";
                $section .= "- Field Match: {$similarities['field_match_percentage']}%\n";
                $section .= "- Matching Components: {$similarities['matching_components']}/{$similarities['total_components']}\n";
                $section .= "\n";
            }

            if (!empty($result['differences'])) {
                $section .= "**Notable Differences:**\n";
                foreach (array_slice($result['differences'], 0, 5) as $diff) {
                    $section .= "- {$diff['type']}: ";
                    if (isset($diff['field'])) {
                        $section .= "Field '{$diff['field']}' differs\n";
                    } else {
                        $section .= json_encode($diff)."\n";
                    }
                }
                $section .= "\n";
            }
        }

        $section .= "---\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateOccurrenceComparisonSection(array $results): string
    {
        $section = "## Occurrence Generation Comparison\n\n";

        foreach ($results as $index => $result) {
            if (!isset($result['occurrence_similarities'])) {
                continue;
            }

            $section .= '### Test Case '.($index + 1)." - Occurrence Results\n\n";

            $occSimilarities = $result['occurrence_similarities'];
            $section .= "**Occurrence Generation Metrics:**\n";
            $section .= "- Component Match: {$occSimilarities['component_match_percentage']}%\n";
            $section .= "- Occurrence Match: {$occSimilarities['occurrence_match_percentage']}%\n";
            $section .= "- Matching Components: {$occSimilarities['matching_components']}/{$occSimilarities['total_components']}\n";
            $section .= "\n";

            if (!empty($result['occurrence_differences'])) {
                $section .= "**Occurrence Differences:**\n";
                foreach (array_slice($result['occurrence_differences'], 0, 3) as $diff) {
                    $section .= "- {$diff['type']}";
                    if (isset($diff['uid'])) {
                        $section .= " (UID: {$diff['uid']})";
                    }
                    $section .= "\n";
                }
                $section .= "\n";
            }
        }

        $section .= "---\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateDifferencesSection(array $results): string
    {
        $section = "## Detailed Differences Analysis\n\n";

        $allDifferences = [];
        foreach ($results as $result) {
            if (!empty($result['differences'])) {
                $allDifferences = array_merge($allDifferences, $result['differences']);
            }
            if (!empty($result['occurrence_differences'])) {
                $allDifferences = array_merge($allDifferences, $result['occurrence_differences']);
            }
        }

        $differenceTypes = [];
        foreach ($allDifferences as $diff) {
            $type = $diff['type'];
            if (!isset($differenceTypes[$type])) {
                $differenceTypes[$type] = 0;
            }
            ++$differenceTypes[$type];
        }

        $section .= "### Difference Categories\n\n";
        foreach ($differenceTypes as $type => $count) {
            $section .= "- **{$type}:** {$count} occurrences\n";
        }
        $section .= "\n";

        $section .= "### Common Difference Patterns\n\n";
        $section .= "The analysis reveals patterns in how Rruler and sabre/vobject handle:\n";
        $section .= "- Date format parsing variations\n";
        $section .= "- Property extraction differences\n";
        $section .= "- RRULE interpretation edge cases\n";
        $section .= "- Component boundary detection\n\n";

        $section .= "---\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateRecommendationsSection(array $results): string
    {
        $section = "## Recommendations\n\n";

        // Calculate overall success rates for recommendations
        $totalTests = count($results);
        $highCompatibilityTests = 0;
        $mediumCompatibilityTests = 0;

        foreach ($results as $result) {
            if (isset($result['similarities']['field_match_percentage'])) {
                $matchRate = $result['similarities']['field_match_percentage'];
                if ($matchRate >= 90) {
                    ++$highCompatibilityTests;
                } elseif ($matchRate >= 70) {
                    ++$mediumCompatibilityTests;
                }
            }
        }

        $highCompatibilityRate = $totalTests > 0 ? round(($highCompatibilityTests / $totalTests) * 100, 2) : 0;

        if ($highCompatibilityRate >= 80) {
            $section .= "### 🟢 High Compatibility Status\n\n";
            $section .= "Rruler demonstrates strong compatibility with sabre/vobject ({$highCompatibilityRate}% of tests show >90% compatibility).\n\n";
            $section .= "**Recommended Actions:**\n";
            $section .= "- Continue monitoring compatibility with regular testing\n";
            $section .= "- Focus optimization efforts on performance improvements\n";
            $section .= "- Document known minor differences for user guidance\n\n";
        } elseif ($highCompatibilityRate >= 60) {
            $section .= "### 🟡 Moderate Compatibility Status\n\n";
            $section .= "Rruler shows moderate compatibility with sabre/vobject ({$highCompatibilityRate}% high compatibility rate).\n\n";
            $section .= "**Recommended Actions:**\n";
            $section .= "- Investigate and address major parsing differences\n";
            $section .= "- Enhance error handling for edge cases\n";
            $section .= "- Improve date format parsing robustness\n\n";
        } else {
            $section .= "### 🔴 Low Compatibility Status\n\n";
            $section .= "Rruler requires significant compatibility improvements ({$highCompatibilityRate}% high compatibility rate).\n\n";
            $section .= "**Recommended Actions:**\n";
            $section .= "- Priority focus on core parsing compatibility\n";
            $section .= "- Comprehensive review of RRULE interpretation logic\n";
            $section .= "- Enhanced validation and error handling\n\n";
        }

        $section .= "---\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generateAppendixSection(array $results): string
    {
        $section = "## Appendix\n\n";

        $section .= "### Test Configuration\n";
        $section .= '- **Rruler Version:** '.(defined('RRULER_VERSION') ? RRULER_VERSION : 'Development')."\n";
        $section .= '- **sabre/vobject Version:** '.(class_exists('Sabre\VObject\Version') ? \Sabre\VObject\Version::VERSION : 'Unknown')."\n";
        $section .= '- **PHP Version:** '.PHP_VERSION."\n";
        $section .= "- **Test Data Sources:** Synthetic, Microsoft Outlook, Google Calendar, Apple Calendar\n";
        $section .= "- **Test Scope:** Enhanced iCalendar compatibility with complex multi-component files\n\n";

        $section .= "### Methodology\n";
        $section .= "1. **Parsing Comparison:** Both libraries parse identical iCalendar content\n";
        $section .= "2. **Occurrence Generation:** RRULE components generate occurrences for comparison\n";
        $section .= "3. **Result Normalization:** Results normalized to common format for comparison\n";
        $section .= "4. **Difference Analysis:** Systematic identification of parsing and generation differences\n";
        $section .= "5. **Similarity Calculation:** Statistical analysis of compatibility metrics\n\n";

        return $section;
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generatePerformanceExecutiveSummary(array $results): string
    {
        // Implementation for performance summary
        return "## Performance Executive Summary\n\n*Performance analysis implementation pending*\n\n---\n\n";
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generatePerformanceMetricsSection(array $results): string
    {
        // Implementation for performance metrics
        return "## Performance Metrics\n\n*Performance metrics implementation pending*\n\n---\n\n";
    }

    /**
     * @param array<string, mixed> $results
     */
    private function generatePerformanceAnalysisSection(array $results): string
    {
        // Implementation for performance analysis
        return "## Performance Analysis\n\n*Performance analysis implementation pending*\n\n---\n\n";
    }

    private function ensureReportDirectoryExists(): void
    {
        if (!is_dir($this->reportDirectory)) {
            mkdir($this->reportDirectory, 0755, true);
        }
    }

    private function saveReport(string $content, string $testName, string $timestamp): string
    {
        $filename = $testName.'_'.date('Y-m-d_H-i-s', strtotime($timestamp)).'.md';
        $filepath = $this->reportDirectory.'/'.$filename;

        file_put_contents($filepath, $content);

        return $filepath;
    }
}
