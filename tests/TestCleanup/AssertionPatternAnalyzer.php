<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\TestCleanup;

/**
 * Utility for analyzing assertion patterns in test files.
 *
 * This analyzer identifies string-content assertions that should be replaced
 * with behavioral assertions focusing on functional outcomes rather than
 * implementation details.
 */
class AssertionPatternAnalyzer
{
    /**
     * Find string-based assertion patterns in test content.
     *
     * @param string $content Test file content to analyze
     * @return array Array of found patterns with line numbers and content
     */
    public function findStringAssertions(string $content): array
    {
        $patterns = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/\$(this|self)->assert(Equals|Same)\s*\(\s*["\'][^"\']*["\']/', $line)) {
                $patterns[] = [
                    'line_number' => $lineNumber + 1,
                    'line' => trim($line),
                    'type' => 'string_comparison',
                ];
            }
        }

        return $patterns;
    }

    /**
     * Find toString() method usage in assertions.
     *
     * @param string $content Test file content to analyze
     * @return array Array of found toString() usage patterns
     */
    public function findToStringUsage(string $content): array
    {
        $patterns = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/->toString\s*\(\s*\)/', $line)) {
                $patterns[] = [
                    'line_number' => $lineNumber + 1,
                    'line' => trim($line),
                    'type' => 'toString_usage',
                ];
            }
        }

        return $patterns;
    }

    /**
     * Find assertStringContains patterns.
     *
     * @param string $content Test file content to analyze
     * @return array Array of found string contains assertions
     */
    public function findStringContainsAssertions(string $content): array
    {
        $patterns = [];
        $lines = explode("\n", $content);

        foreach ($lines as $lineNumber => $line) {
            if (preg_match('/\$(this|self)->assertStringContains/', $line)) {
                $patterns[] = [
                    'line_number' => $lineNumber + 1,
                    'line' => trim($line),
                    'type' => 'string_contains',
                ];
            }
        }

        return $patterns;
    }

    /**
     * Suggest behavioral alternatives for string assertions.
     *
     * @param string $assertion The assertion line to analyze
     * @return array Suggested improvements
     */
    public function suggestBehavioralAlternative(string $assertion): array
    {
        $suggestions = [];

        if (preg_match('/assertEquals\s*\(\s*["\']([^"\']*)["\']/', $assertion, $matches)) {
            $expectedValue = $matches[1];

            $suggestions = [
                'pattern' => 'string_comparison',
                'original' => $assertion,
                'issue' => 'Tests string value rather than behavior',
                'suggestion' => 'Consider testing the functional outcome that produces this value',
            ];

            // Provide specific suggestions based on common patterns
            if (in_array($expectedValue, ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'])) {
                $suggestions['specific_suggestion'] = 'Test that the frequency behavior works correctly rather than string value';
            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $expectedValue)) {
                $suggestions['specific_suggestion'] = 'Test that the date calculation produces correct behavior rather than specific string format';
            }
        }

        if (strpos($assertion, '->toString()') !== false) {
            $suggestions = [
                'pattern' => 'toString_usage',
                'original' => $assertion,
                'issue' => 'Tests string representation rather than functional behavior',
                'suggestion' => 'Test the underlying functionality that toString() represents',
            ];
        }

        return $suggestions;
    }

    /**
     * Determine if an assertion is behavioral or string-based.
     *
     * @param string $assertion The assertion to classify
     * @return bool True if behavioral, false if string-based
     */
    public function isBehavioralAssertion(string $assertion): bool
    {
        // Behavioral patterns
        $behavioralPatterns = [
            '/\$(this|self)->assert(True|False)\s*\(/',
            '/\$(this|self)->assertInstanceOf\s*\(/',
            '/\$(this|self)->assertCount\s*\(/',
            '/\$(this|self)->assertArrayHasKey\s*\(/',
            '/\$(this|self)->assertEmpty\s*\(/',
            '/\$(this|self)->assertNotEmpty\s*\(/',
            '/\$(this|self)->assertNull\s*\(/',
            '/\$(this|self)->assertNotNull\s*\(/',
        ];

        foreach ($behavioralPatterns as $pattern) {
            if (preg_match($pattern, $assertion)) {
                return true;
            }
        }

        // String-based patterns
        $stringPatterns = [
            '/\$(this|self)->assert(Equals|Same)\s*\(\s*["\']/',
            '/\$(this|self)->assertStringContains\s*\(/',
            '/->toString\s*\(\s*\)/',
        ];

        foreach ($stringPatterns as $pattern) {
            if (preg_match($pattern, $assertion)) {
                return false;
            }
        }

        // Default to behavioral if unclear
        return true;
    }

    /**
     * Analyze a test file for assertion patterns.
     *
     * @param string $filePath Path to test file to analyze
     * @return array Analysis results
     */
    public function analyzeFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$filePath}");
        }

        $content = file_get_contents($filePath);
        $lines = explode("\n", $content);

        $stringAssertions = 0;
        $behavioralAssertions = 0;
        $toStringUsage = 0;

        foreach ($lines as $line) {
            if (preg_match('/\$(this|self)->assert\w+\s*\(/', $line)) {
                if ($this->isBehavioralAssertion($line)) {
                    ++$behavioralAssertions;
                } else {
                    ++$stringAssertions;
                }
            }

            if (preg_match('/->toString\s*\(\s*\)/', $line)) {
                ++$toStringUsage;
            }
        }

        return [
            'file_path' => $filePath,
            'string_assertions' => $stringAssertions,
            'behavioral_assertions' => $behavioralAssertions,
            'toString_usage' => $toStringUsage,
            'total_assertions' => $stringAssertions + $behavioralAssertions,
            'string_percentage' => $stringAssertions + $behavioralAssertions > 0
                ? round(($stringAssertions / ($stringAssertions + $behavioralAssertions)) * 100, 1)
                : 0,
        ];
    }

    /**
     * Generate a summary report of assertion pattern analysis.
     *
     * @param array $analysisData Aggregated analysis data
     * @return string Formatted summary report
     */
    public function generateSummaryReport(array $analysisData): string
    {
        $report = "Assertion Pattern Analysis Summary\n";
        $report .= "=====================================\n\n";

        $report .= sprintf("Files analyzed: %d\n", $analysisData['total_files']);
        $report .= sprintf("Files with string assertions: %d\n", $analysisData['files_with_string_assertions']);
        $report .= sprintf("Total string assertions found: %d\n", $analysisData['total_string_assertions']);
        $report .= sprintf("Total behavioral assertions: %d\n", $analysisData['total_behavioral_assertions']);

        $totalAssertions = $analysisData['total_string_assertions'] + $analysisData['total_behavioral_assertions'];
        if ($totalAssertions > 0) {
            $stringPercentage = round(($analysisData['total_string_assertions'] / $totalAssertions) * 100, 1);
            $report .= sprintf("String assertion percentage: %.1f%%\n", $stringPercentage);
        }

        if (isset($analysisData['improvement_opportunities']) && !empty($analysisData['improvement_opportunities'])) {
            $report .= "\nImprovement Opportunities:\n";
            foreach ($analysisData['improvement_opportunities'] as $opportunity) {
                $report .= "- {$opportunity}\n";
            }
        }

        return $report;
    }
}
