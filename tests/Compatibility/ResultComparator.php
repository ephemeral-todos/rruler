<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;

/**
 * Utility for normalizing and comparing RRULE parsing and occurrence generation results
 * between Rruler and sabre/vobject implementations.
 */
final class ResultComparator
{
    /**
     * Compare two arrays of DateTimeImmutable occurrences for equality.
     *
     * @param array<DateTimeImmutable> $expected Expected occurrences (typically from sabre/vobject)
     * @param array<DateTimeImmutable> $actual Actual occurrences (typically from Rruler)
     */
    public static function compareOccurrences(array $expected, array $actual): ComparisonResult
    {
        $result = new ComparisonResult();

        // Check count mismatch
        if (count($expected) !== count($actual)) {
            $result->addMismatch('count', [
                'expected' => count($expected),
                'actual' => count($actual),
                'message' => sprintf(
                    'Occurrence count mismatch: expected %d, got %d',
                    count($expected),
                    count($actual)
                ),
            ]);
        }

        // Compare individual occurrences
        $maxCount = max(count($expected), count($actual));
        for ($i = 0; $i < $maxCount; ++$i) {
            $expectedOccurrence = $expected[$i] ?? null;
            $actualOccurrence = $actual[$i] ?? null;

            if ($expectedOccurrence === null) {
                $result->addMismatch("occurrence_{$i}", [
                    'expected' => null,
                    'actual' => $actualOccurrence->format('Y-m-d H:i:s'),
                    'message' => "Unexpected extra occurrence at position {$i}: {$actualOccurrence->format('Y-m-d H:i:s')}",
                ]);
                continue;
            }

            if ($actualOccurrence === null) {
                $result->addMismatch("occurrence_{$i}", [
                    'expected' => $expectedOccurrence->format('Y-m-d H:i:s'),
                    'actual' => null,
                    'message' => "Missing occurrence at position {$i}: expected {$expectedOccurrence->format('Y-m-d H:i:s')}",
                ]);
                continue;
            }

            // Compare timestamps to handle potential timezone differences
            if ($expectedOccurrence->getTimestamp() !== $actualOccurrence->getTimestamp()) {
                $result->addMismatch("occurrence_{$i}", [
                    'expected' => $expectedOccurrence->format('Y-m-d H:i:s T'),
                    'actual' => $actualOccurrence->format('Y-m-d H:i:s T'),
                    'message' => sprintf(
                        'Occurrence mismatch at position %d: expected %s, got %s',
                        $i,
                        $expectedOccurrence->format('Y-m-d H:i:s T'),
                        $actualOccurrence->format('Y-m-d H:i:s T')
                    ),
                ]);
            }
        }

        $result->setMatches(empty($result->getMismatches()));

        return $result;
    }

    /**
     * Normalize DateTime objects to a consistent format for comparison.
     * Handles potential timezone and formatting differences between implementations.
     *
     * @param array<DateTimeImmutable> $occurrences
     * @return array<DateTimeImmutable>
     */
    public static function normalizeOccurrences(array $occurrences): array
    {
        return array_map(
            static fn (DateTimeImmutable $dt) => $dt->setTimezone(new \DateTimeZone('UTC')),
            $occurrences
        );
    }

    /**
     * Format occurrences for human-readable output.
     *
     * @param array<DateTimeImmutable> $occurrences
     * @param int $maxDisplay Maximum number of occurrences to display (0 = all)
     */
    public static function formatOccurrences(array $occurrences, int $maxDisplay = 10): string
    {
        if (empty($occurrences)) {
            return '[]';
        }

        $displayOccurrences = $maxDisplay > 0
            ? array_slice($occurrences, 0, $maxDisplay)
            : $occurrences;

        $formatted = array_map(
            static fn (DateTimeImmutable $dt) => $dt->format('Y-m-d H:i:s'),
            $displayOccurrences
        );

        $result = '['.implode(', ', $formatted);

        if ($maxDisplay > 0 && count($occurrences) > $maxDisplay) {
            $result .= sprintf(' ... (%d more)', count($occurrences) - $maxDisplay);
        }

        $result .= ']';

        return $result;
    }

    /**
     * Calculate basic statistics about occurrence differences.
     *
     * @param array<DateTimeImmutable> $expected
     * @param array<DateTimeImmutable> $actual
     * @return array<string, mixed>
     */
    public static function calculateStats(array $expected, array $actual): array
    {
        $stats = [
            'expected_count' => count($expected),
            'actual_count' => count($actual),
            'count_match' => count($expected) === count($actual),
            'perfect_match' => true,
            'first_mismatch_index' => null,
            'accuracy_percentage' => 0.0,
        ];

        if (empty($expected) && empty($actual)) {
            $stats['accuracy_percentage'] = 100.0;

            return $stats;
        }

        if (empty($expected) || empty($actual)) {
            $stats['perfect_match'] = false;
            $stats['first_mismatch_index'] = 0;

            return $stats;
        }

        $matches = 0;
        $maxCount = max(count($expected), count($actual));

        for ($i = 0; $i < min(count($expected), count($actual)); ++$i) {
            if ($expected[$i]->getTimestamp() === $actual[$i]->getTimestamp()) {
                ++$matches;
            } elseif ($stats['first_mismatch_index'] === null) {
                $stats['first_mismatch_index'] = $i;
                $stats['perfect_match'] = false;
            }
        }

        // If arrays have different lengths, that's also a mismatch
        if (count($expected) !== count($actual)) {
            $stats['perfect_match'] = false;
            if ($stats['first_mismatch_index'] === null) {
                $stats['first_mismatch_index'] = min(count($expected), count($actual));
            }
        }

        $stats['accuracy_percentage'] = $maxCount > 0 ? ($matches / $maxCount) * 100.0 : 0.0;

        return $stats;
    }

    /**
     * Generate a detailed comparison report.
     *
     * @param string $rruleString The RRULE being tested
     * @param DateTimeImmutable $startDate The start date used
     * @param array<DateTimeImmutable> $expected Expected results
     * @param array<DateTimeImmutable> $actual Actual results
     * @param string $testDescription Optional test description
     */
    public static function generateReport(
        string $rruleString,
        DateTimeImmutable $startDate,
        array $expected,
        array $actual,
        string $testDescription = '',
    ): string {
        $comparison = self::compareOccurrences($expected, $actual);
        $stats = self::calculateStats($expected, $actual);

        $report = [];
        $report[] = '=== COMPATIBILITY TEST REPORT ===';

        if ($testDescription) {
            $report[] = "Test: {$testDescription}";
        }

        $report[] = "RRULE: {$rruleString}";
        $report[] = 'Start Date: '.$startDate->format('Y-m-d H:i:s T');
        $report[] = '';

        $report[] = '--- RESULTS SUMMARY ---';
        $report[] = 'Match Status: '.($comparison->matches() ? '✅ PERFECT MATCH' : '❌ MISMATCH DETECTED');
        $report[] = "Expected Count: {$stats['expected_count']}";
        $report[] = "Actual Count: {$stats['actual_count']}";
        $report[] = 'Accuracy: '.number_format($stats['accuracy_percentage'], 1).'%';

        if (!$comparison->matches()) {
            $report[] = '';
            $report[] = '--- MISMATCHES ---';
            foreach ($comparison->getMismatches() as $type => $details) {
                $report[] = "• {$details['message']}";
            }
        }

        $report[] = '';
        $report[] = '--- EXPECTED OCCURRENCES ---';
        $report[] = self::formatOccurrences($expected);

        $report[] = '';
        $report[] = '--- ACTUAL OCCURRENCES ---';
        $report[] = self::formatOccurrences($actual);

        return implode("\n", $report);
    }
}

/**
 * Result object for comparison operations.
 */
final class ComparisonResult
{
    private bool $matches = false;
    private array $mismatches = [];

    public function matches(): bool
    {
        return $this->matches;
    }

    public function setMatches(bool $matches): void
    {
        $this->matches = $matches;
    }

    /**
     * @return array<string, array{expected: mixed, actual: mixed, message: string}>
     */
    public function getMismatches(): array
    {
        return $this->mismatches;
    }

    /**
     * @param array{expected: mixed, actual: mixed, message: string} $details
     */
    public function addMismatch(string $type, array $details): void
    {
        $this->mismatches[$type] = $details;
    }
}
