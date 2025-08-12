<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Reporting;

/**
 * Hybrid Compatibility Reporter.
 *
 * This class provides comprehensive reporting for both sabre/vobject and
 * python-dateutil compatibility validation results, offering insights into
 * the differences between RFC 5545 implementations.
 */
final class HybridCompatibilityReporter
{
    /** @var array<string, array<string, mixed>> */
    private array $sabreResults = [];
    /** @var array<string, array<string, mixed>> */
    private array $pythonDateutilResults = [];
    /** @var array<string, mixed> */
    private array $performanceMetrics = [];

    /**
     * Record a sabre/vobject compatibility test result.
     *
     * @param string $testName Name of the test
     * @param string $rrule The RRULE being tested
     * @param bool $passed Whether the test passed
     * @param float $executionTime Execution time in milliseconds
     * @param array<string> $occurrences Generated occurrences
     * @param string $description Test description
     */
    public function recordSabreResult(
        string $testName,
        string $rrule,
        bool $passed,
        float $executionTime,
        array $occurrences,
        string $description = '',
    ): void {
        $this->sabreResults[$testName] = [
            'rrule' => $rrule,
            'passed' => $passed,
            'execution_time_ms' => $executionTime,
            'occurrences' => $occurrences,
            'occurrence_count' => count($occurrences),
            'description' => $description,
            'validation_type' => 'sabre/vobject',
        ];
    }

    /**
     * Record a python-dateutil fixture validation result.
     *
     * @param string $testName Name of the test
     * @param string $rrule The RRULE being tested
     * @param bool $passed Whether the test passed
     * @param float $executionTime Execution time in milliseconds
     * @param array<string> $actualOccurrences Rruler generated occurrences
     * @param array<string> $expectedOccurrences Python-dateutil expected occurrences
     * @param string $fixtureName Fixture file name
     * @param string $description Test description
     */
    public function recordPythonDateutilResult(
        string $testName,
        string $rrule,
        bool $passed,
        float $executionTime,
        array $actualOccurrences,
        array $expectedOccurrences,
        string $fixtureName,
        string $description = '',
    ): void {
        $this->pythonDateutilResults[$testName] = [
            'rrule' => $rrule,
            'passed' => $passed,
            'execution_time_ms' => $executionTime,
            'actual_occurrences' => $actualOccurrences,
            'expected_occurrences' => $expectedOccurrences,
            'occurrence_count' => count($actualOccurrences),
            'fixture_name' => $fixtureName,
            'description' => $description,
            'validation_type' => 'python-dateutil',
            'matches_expected' => $actualOccurrences === $expectedOccurrences,
        ];
    }

    /**
     * Record performance metrics.
     *
     * @param string $metric Metric name
     * @param mixed $value Metric value
     */
    public function recordPerformanceMetric(string $metric, mixed $value): void
    {
        $this->performanceMetrics[$metric] = $value;
    }

    /**
     * Generate a comprehensive HTML report.
     *
     * @return string HTML report content
     */
    public function generateHtmlReport(): string
    {
        $html = $this->getHtmlHeader();
        $html .= $this->generateSummarySection();
        $html .= $this->generateSabreResultsSection();
        $html .= $this->generatePythonDateutilResultsSection();
        $html .= $this->generatePerformanceSection();
        $html .= $this->generateComparisonSection();
        $html .= $this->getHtmlFooter();

        return $html;
    }

    /**
     * Generate a summary section of the report.
     */
    private function generateSummarySection(): string
    {
        $sabreTotal = count($this->sabreResults);
        $sabrePassed = count(array_filter($this->sabreResults, fn (array $r): bool => (bool) $r['passed']));
        $pythonTotal = count($this->pythonDateutilResults);
        $pythonPassed = count(array_filter($this->pythonDateutilResults, fn (array $r): bool => (bool) $r['passed']));

        $sabrePercentage = $sabreTotal > 0 ? round(($sabrePassed / $sabreTotal) * 100, 1) : 0;
        $pythonPercentage = $pythonTotal > 0 ? round(($pythonPassed / $pythonTotal) * 100, 1) : 0;

        return "
        <div class='summary-section'>
            <h2>Compatibility Validation Summary</h2>
            <div class='summary-grid'>
                <div class='summary-card sabre'>
                    <h3>sabre/vobject Compatibility</h3>
                    <div class='metric-large'>{$sabrePercentage}%</div>
                    <div class='metric-detail'>{$sabrePassed}/{$sabreTotal} tests passed</div>
                </div>
                <div class='summary-card python'>
                    <h3>python-dateutil Compatibility</h3>
                    <div class='metric-large'>{$pythonPercentage}%</div>
                    <div class='metric-detail'>{$pythonPassed}/{$pythonTotal} tests passed</div>
                </div>
            </div>
        </div>";
    }

    /**
     * Generate sabre/vobject results section.
     */
    private function generateSabreResultsSection(): string
    {
        if (empty($this->sabreResults)) {
            return '<div class="section"><h2>sabre/vobject Results</h2><p>No sabre/vobject tests recorded.</p></div>';
        }

        $html = '<div class="section"><h2>sabre/vobject Compatibility Results</h2><table class="results-table">';
        $html .= '<thead><tr><th>Test Name</th><th>RRULE</th><th>Status</th><th>Occurrences</th><th>Time (ms)</th><th>Description</th></tr></thead><tbody>';

        foreach ($this->sabreResults as $testName => $result) {
            $status = $result['passed'] ? '<span class="status-pass">PASS</span>' : '<span class="status-fail">FAIL</span>';
            $rrule = isset($result['rrule']) && (is_string($result['rrule']) || is_numeric($result['rrule'])) ? (string) $result['rrule'] : '';
            $occurrenceCount = isset($result['occurrence_count']) && (is_string($result['occurrence_count']) || is_numeric($result['occurrence_count'])) ? (string) $result['occurrence_count'] : '0';
            $executionTime = isset($result['execution_time_ms']) && (is_string($result['execution_time_ms']) || is_numeric($result['execution_time_ms'])) ? (string) $result['execution_time_ms'] : '0';
            $description = isset($result['description']) && (is_string($result['description']) || is_numeric($result['description'])) ? (string) $result['description'] : '';

            $html .= "<tr>
                <td>{$testName}</td>
                <td><code>".htmlspecialchars($rrule)."</code></td>
                <td>{$status}</td>
                <td>".$occurrenceCount.'</td>
                <td>'.$executionTime.'</td>
                <td>'.htmlspecialchars($description).'</td>
            </tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Generate python-dateutil results section.
     */
    private function generatePythonDateutilResultsSection(): string
    {
        if (empty($this->pythonDateutilResults)) {
            return '<div class="section"><h2>python-dateutil Results</h2><p>No python-dateutil tests recorded.</p></div>';
        }

        $html = '<div class="section"><h2>python-dateutil Fixture Validation Results</h2><table class="results-table">';
        $html .= '<thead><tr><th>Test Name</th><th>RRULE</th><th>Status</th><th>Match</th><th>Occurrences</th><th>Time (ms)</th><th>Fixture</th></tr></thead><tbody>';

        foreach ($this->pythonDateutilResults as $testName => $result) {
            $status = $result['passed'] ? '<span class="status-pass">PASS</span>' : '<span class="status-fail">FAIL</span>';
            $match = $result['matches_expected'] ? '<span class="status-pass">✓</span>' : '<span class="status-fail">✗</span>';
            $rrule = isset($result['rrule']) && (is_string($result['rrule']) || is_numeric($result['rrule'])) ? (string) $result['rrule'] : '';
            $occurrenceCount = isset($result['occurrence_count']) && (is_string($result['occurrence_count']) || is_numeric($result['occurrence_count'])) ? (string) $result['occurrence_count'] : '0';
            $executionTime = isset($result['execution_time_ms']) && (is_string($result['execution_time_ms']) || is_numeric($result['execution_time_ms'])) ? (string) $result['execution_time_ms'] : '0';
            $fixtureName = isset($result['fixture_name']) && (is_string($result['fixture_name']) || is_numeric($result['fixture_name'])) ? (string) $result['fixture_name'] : '';

            $html .= "<tr>
                <td>{$testName}</td>
                <td><code>".htmlspecialchars($rrule)."</code></td>
                <td>{$status}</td>
                <td>{$match}</td>
                <td>".$occurrenceCount.'</td>
                <td>'.$executionTime.'</td>
                <td>'.htmlspecialchars($fixtureName).'</td>
            </tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Generate performance metrics section.
     */
    private function generatePerformanceSection(): string
    {
        if (empty($this->performanceMetrics)) {
            return '<div class="section"><h2>Performance Metrics</h2><p>No performance metrics recorded.</p></div>';
        }

        $html = '<div class="section"><h2>Performance Metrics</h2><table class="metrics-table">';
        $html .= '<thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>';

        foreach ($this->performanceMetrics as $metric => $value) {
            $stringValue = (is_string($value) || is_numeric($value)) ? (string) $value : '';
            $formattedValue = is_numeric($value) ? number_format((float) $value, 2) : $stringValue;
            $html .= '<tr><td>'.htmlspecialchars($metric).'</td><td>'.htmlspecialchars($formattedValue).'</td></tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }

    /**
     * Generate comparison section highlighting differences.
     */
    private function generateComparisonSection(): string
    {
        $html = '<div class="section"><h2>Implementation Comparison</h2>';

        if (empty($this->sabreResults) || empty($this->pythonDateutilResults)) {
            return $html.'<p>Comparison requires both sabre/vobject and python-dateutil results.</p></div>';
        }

        $html .= '<p>This section highlights key differences between sabre/vobject and python-dateutil implementations:</p>';
        $html .= '<ul>';
        $html .= '<li><strong>Start Date Handling:</strong> sabre/vobject includes start date per iCalendar convention, python-dateutil follows pure RRULE semantics</li>';
        $html .= '<li><strong>Edge Case Behavior:</strong> Minor differences in handling boundary conditions and complex patterns</li>';
        $html .= '<li><strong>Performance:</strong> Both validation approaches provide fast, reliable RRULE validation</li>';
        $html .= '</ul>';

        return $html.'</div>';
    }

    /**
     * Get HTML header with CSS styles.
     */
    private function getHtmlHeader(): string
    {
        return '<!DOCTYPE html>
        <html>
        <head>
            <title>Rruler Hybrid Compatibility Report</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
                .summary-section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px; }
                .summary-card { padding: 20px; border-radius: 8px; text-align: center; }
                .summary-card.sabre { background: #e3f2fd; border-left: 4px solid #2196f3; }
                .summary-card.python { background: #e8f5e8; border-left: 4px solid #4caf50; }
                .metric-large { font-size: 2.5em; font-weight: bold; color: #333; }
                .metric-detail { color: #666; margin-top: 5px; }
                .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
                .results-table, .metrics-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
                .results-table th, .results-table td, .metrics-table th, .metrics-table td { 
                    padding: 10px; text-align: left; border-bottom: 1px solid #ddd; 
                }
                .results-table th, .metrics-table th { background: #f8f9fa; font-weight: bold; }
                .status-pass { color: #4caf50; font-weight: bold; }
                .status-fail { color: #f44336; font-weight: bold; }
                code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
                h1 { color: #333; text-align: center; }
                h2 { color: #444; border-bottom: 2px solid #eee; padding-bottom: 10px; }
            </style>
        </head>
        <body>
            <h1>Rruler Hybrid Compatibility Report</h1>
            <p style="text-align: center; color: #666; margin-bottom: 30px;">
                Generated on '.date('Y-m-d H:i:s').' | RFC 5545 RRULE Validation Results
            </p>';
    }

    /**
     * Get HTML footer.
     */
    private function getHtmlFooter(): string
    {
        return '</body></html>';
    }

    /**
     * Get all results as array for programmatic access.
     *
     * @return array<string, mixed> Complete results data
     */
    public function getResults(): array
    {
        return [
            'sabre_results' => $this->sabreResults,
            'python_dateutil_results' => $this->pythonDateutilResults,
            'performance_metrics' => $this->performanceMetrics,
            'summary' => [
                'sabre_tests' => count($this->sabreResults),
                'sabre_passed' => count(array_filter($this->sabreResults, fn (array $r): bool => (bool) $r['passed'])),
                'python_tests' => count($this->pythonDateutilResults),
                'python_passed' => count(array_filter($this->pythonDateutilResults, fn (array $r): bool => (bool) $r['passed'])),
            ],
        ];
    }

    /**
     * Clear all recorded results.
     */
    public function clear(): void
    {
        $this->sabreResults = [];
        $this->pythonDateutilResults = [];
        $this->performanceMetrics = [];
    }
}
