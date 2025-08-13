<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Utilities;

use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Component\VTodo;
use Sabre\VObject\Reader;

/**
 * Enhanced iCalendar compatibility testing framework for comparing Rruler
 * against sabre/vobject parsing and occurrence generation.
 */
final class EnhancedIcalCompatibilityFramework
{
    private IcalParser $rrulerIcalParser;
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $occurrenceGenerator;

    public function __construct()
    {
        $this->rrulerIcalParser = new IcalParser();
        $this->rruler = new Rruler();
        $this->occurrenceGenerator = new DefaultOccurrenceGenerator();
    }

    /**
     * Compare parsing results between Rruler and sabre/vobject for a given iCalendar string.
     *
     * @param string $icalContent iCalendar content to parse
     * @return array Comparison results with differences and similarities
     */
    public function compareParsingResults(string $icalContent): array
    {
        $results = [
            'rruler_results' => [],
            'sabre_results' => [],
            'differences' => [],
            'similarities' => [],
            'parsing_errors' => [],
        ];

        try {
            // Parse with Rruler
            $rrulerResults = $this->rrulerIcalParser->parse($icalContent);
            $results['rruler_results'] = $this->normalizeRrulerResults($rrulerResults);
        } catch (\Exception $e) {
            $results['parsing_errors']['rruler'] = $e->getMessage();
        }

        try {
            // Parse with sabre/vobject
            $sabreCalendar = Reader::read($icalContent);
            $results['sabre_results'] = $this->normalizeSabreResults($sabreCalendar);
        } catch (\Exception $e) {
            $results['parsing_errors']['sabre'] = $e->getMessage();
        }

        // Compare results
        $results['differences'] = $this->identifyDifferences(
            $results['rruler_results'],
            $results['sabre_results']
        );

        $results['similarities'] = $this->identifySimilarities(
            $results['rruler_results'],
            $results['sabre_results']
        );

        return $results;
    }

    /**
     * Compare occurrence generation between Rruler and sabre/vobject for components with RRULEs.
     *
     * @param string $icalContent iCalendar content with RRULE components
     * @param int $maxOccurrences Maximum number of occurrences to generate for comparison
     * @return array Occurrence generation comparison results
     */
    public function compareOccurrenceGeneration(string $icalContent, int $maxOccurrences = 50): array
    {
        $results = [
            'rruler_occurrences' => [],
            'sabre_occurrences' => [],
            'occurrence_differences' => [],
            'occurrence_similarities' => [],
            'generation_errors' => [],
        ];

        try {
            // Generate occurrences with Rruler
            $rrulerComponents = $this->rrulerIcalParser->parse($icalContent);
            foreach ($rrulerComponents as $component) {
                if (isset($component['rrule']) && isset($component['dateTimeContext'])) {
                    $occurrences = [];
                    $count = 0;
                    foreach ($this->occurrenceGenerator->generateOccurrences(
                        $component['rrule'],
                        $component['dateTimeContext']->getDateTime(),
                        $maxOccurrences
                    ) as $occurrence) {
                        $occurrences[] = $occurrence->format('Y-m-d H:i:s');
                        ++$count;
                        if ($count >= $maxOccurrences) {
                            break;
                        }
                    }
                    $results['rruler_occurrences'][$component['uid'] ?? 'unknown'] = $occurrences;
                }
            }
        } catch (\Exception $e) {
            $results['generation_errors']['rruler'] = $e->getMessage();
        }

        try {
            // Generate occurrences with sabre/vobject
            $sabreCalendar = Reader::read($icalContent);
            foreach ($sabreCalendar->getComponents() as $component) {
                if (($component instanceof VEvent || $component instanceof VTodo) && isset($component->RRULE)) {
                    $uid = (string) $component->UID;
                    $occurrences = [];
                    $count = 0;

                    try {
                        $iterator = new \Sabre\VObject\Recur\EventIterator($sabreCalendar, $component->UID);
                        while ($iterator->valid() && $count < $maxOccurrences) {
                            $current = $iterator->current();
                            if ($current && isset($current->DTSTART)) {
                                $dtstart = $current->DTSTART->getDateTime();
                                if ($dtstart) {
                                    $occurrences[] = $dtstart->format('Y-m-d H:i:s');
                                }
                            }
                            $iterator->next();
                            ++$count;
                        }
                        $results['sabre_occurrences'][$uid] = $occurrences;
                    } catch (\Exception $iteratorException) {
                        // Skip this component if iterator fails
                        $results['sabre_occurrences'][$uid] = [];
                    }
                }
            }
        } catch (\Exception $e) {
            $results['generation_errors']['sabre'] = $e->getMessage();
        }

        // Compare occurrence results
        $results['occurrence_differences'] = $this->compareOccurrenceArrays(
            $results['rruler_occurrences'],
            $results['sabre_occurrences']
        );

        $results['occurrence_similarities'] = $this->calculateOccurrenceSimilarities(
            $results['rruler_occurrences'],
            $results['sabre_occurrences']
        );

        return $results;
    }

    /**
     * Normalize Rruler parsing results to a standard format for comparison.
     */
    /**
     * @param array<string, mixed> $rrulerResults
     * @return array<string, mixed>
     */
    private function normalizeRrulerResults(array $rrulerResults): array
    {
        $normalized = [];
        foreach ($rrulerResults as $component) {
            $normalized[] = [
                'uid' => $component['uid'] ?? 'unknown',
                'type' => $component['componentType']->value ?? 'unknown',
                'dtstart' => $component['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s') ?? null,
                'summary' => $component['summary'] ?? null,
                'rrule' => $component['rrule'] ? (string) $component['rrule'] : null,
                'properties_count' => count($component),
            ];
        }

        return $normalized;
    }

    /**
     * Normalize sabre/vobject parsing results to a standard format for comparison.
     */
    private function normalizeSabreResults($sabreCalendar): array
    {
        $normalized = [];
        foreach ($sabreCalendar->getComponents() as $component) {
            if ($component instanceof VEvent || $component instanceof VTodo) {
                $normalized[] = [
                    'uid' => (string) $component->UID,
                    'type' => $component->name,
                    'dtstart' => isset($component->DTSTART) ? $component->DTSTART->getDateTime()->format('Y-m-d H:i:s') : null,
                    'summary' => isset($component->SUMMARY) ? (string) $component->SUMMARY : null,
                    'rrule' => isset($component->RRULE) ? (string) $component->RRULE : null,
                    'properties_count' => count($component->children()),
                ];
            }
        }

        return $normalized;
    }

    /**
     * Identify differences between Rruler and sabre/vobject parsing results.
     */
    /**
     * @param array<string, mixed> $rrulerResults
     * @param array<string, mixed> $sabreResults
     * @return array<string, mixed>
     */
    private function identifyDifferences(array $rrulerResults, array $sabreResults): array
    {
        $differences = [];

        // Compare component count
        if (count($rrulerResults) !== count($sabreResults)) {
            $differences[] = [
                'type' => 'component_count',
                'rruler_count' => count($rrulerResults),
                'sabre_count' => count($sabreResults),
            ];
        }

        // Compare individual components
        for ($i = 0; $i < min(count($rrulerResults), count($sabreResults)); ++$i) {
            $rrulerComponent = $rrulerResults[$i];
            $sabreComponent = $sabreResults[$i];

            foreach (['uid', 'type', 'dtstart', 'summary', 'rrule'] as $field) {
                if ($rrulerComponent[$field] !== $sabreComponent[$field]) {
                    $differences[] = [
                        'type' => 'field_difference',
                        'field' => $field,
                        'component_index' => $i,
                        'rruler_value' => $rrulerComponent[$field],
                        'sabre_value' => $sabreComponent[$field],
                    ];
                }
            }
        }

        return $differences;
    }

    /**
     * Identify similarities between Rruler and sabre/vobject parsing results.
     */
    /**
     * @param array<string, mixed> $rrulerResults
     * @param array<string, mixed> $sabreResults
     * @return array<string, mixed>
     */
    private function identifySimilarities(array $rrulerResults, array $sabreResults): array
    {
        $similarities = [];
        $matchingComponents = 0;
        $totalFields = 0;
        $matchingFields = 0;

        for ($i = 0; $i < min(count($rrulerResults), count($sabreResults)); ++$i) {
            $rrulerComponent = $rrulerResults[$i];
            $sabreComponent = $sabreResults[$i];
            $componentMatches = 0;

            foreach (['uid', 'type', 'dtstart', 'summary', 'rrule'] as $field) {
                ++$totalFields;
                if ($rrulerComponent[$field] === $sabreComponent[$field]) {
                    ++$matchingFields;
                    ++$componentMatches;
                }
            }

            if ($componentMatches === 5) { // All fields match
                ++$matchingComponents;
            }
        }

        $similarities['matching_components'] = $matchingComponents;
        $similarities['total_components'] = min(count($rrulerResults), count($sabreResults));
        $similarities['component_match_percentage'] = $similarities['total_components'] > 0
            ? round(($matchingComponents / $similarities['total_components']) * 100, 2)
            : 0;
        $similarities['field_match_percentage'] = $totalFields > 0
            ? round(($matchingFields / $totalFields) * 100, 2)
            : 0;

        return $similarities;
    }

    /**
     * Compare occurrence generation arrays between Rruler and sabre/vobject.
     */
    /**
     * @param array<string, array<\DateTimeImmutable>> $rrulerOccurrences
     * @param array<string, array<\DateTimeImmutable>> $sabreOccurrences
     * @return array<string, mixed>
     */
    private function compareOccurrenceArrays(array $rrulerOccurrences, array $sabreOccurrences): array
    {
        $differences = [];

        foreach ($rrulerOccurrences as $uid => $rrulerOccs) {
            if (!isset($sabreOccurrences[$uid])) {
                $differences[] = [
                    'type' => 'missing_component_in_sabre',
                    'uid' => $uid,
                    'rruler_occurrence_count' => count($rrulerOccs),
                ];
                continue;
            }

            $sabreOccs = $sabreOccurrences[$uid];
            if (count($rrulerOccs) !== count($sabreOccs)) {
                $differences[] = [
                    'type' => 'occurrence_count_mismatch',
                    'uid' => $uid,
                    'rruler_count' => count($rrulerOccs),
                    'sabre_count' => count($sabreOccs),
                ];
            }

            $mismatchedOccurrences = array_diff($rrulerOccs, $sabreOccs);
            if (!empty($mismatchedOccurrences)) {
                $differences[] = [
                    'type' => 'occurrence_value_mismatch',
                    'uid' => $uid,
                    'mismatched_rruler_occurrences' => array_values($mismatchedOccurrences),
                    'mismatched_sabre_occurrences' => array_values(array_diff($sabreOccs, $rrulerOccs)),
                ];
            }
        }

        // Check for components only in sabre results
        foreach ($sabreOccurrences as $uid => $sabreOccs) {
            if (!isset($rrulerOccurrences[$uid])) {
                $differences[] = [
                    'type' => 'missing_component_in_rruler',
                    'uid' => $uid,
                    'sabre_occurrence_count' => count($sabreOccs),
                ];
            }
        }

        return $differences;
    }

    /**
     * Calculate similarity metrics for occurrence generation.
     */
    /**
     * @param array<string, array<\DateTimeImmutable>> $rrulerOccurrences
     * @param array<string, array<\DateTimeImmutable>> $sabreOccurrences
     * @return array<string, mixed>
     */
    private function calculateOccurrenceSimilarities(array $rrulerOccurrences, array $sabreOccurrences): array
    {
        $similarities = [];
        $totalComponents = 0;
        $matchingComponents = 0;
        $totalOccurrences = 0;
        $matchingOccurrences = 0;

        foreach ($rrulerOccurrences as $uid => $rrulerOccs) {
            if (!is_array($rrulerOccs)) {
                continue;
            }
            ++$totalComponents;
            if (isset($sabreOccurrences[$uid]) && is_array($sabreOccurrences[$uid])) {
                $sabreOccs = $sabreOccurrences[$uid];
                $commonOccurrences = array_intersect($rrulerOccs, $sabreOccs);
                $matchingOccurrences += count($commonOccurrences);
                $totalOccurrences += max(count($rrulerOccs), count($sabreOccs));

                if (count($commonOccurrences) === count($rrulerOccs) && count($rrulerOccs) === count($sabreOccs)) {
                    ++$matchingComponents;
                }
            } else {
                $totalOccurrences += count($rrulerOccs);
            }
        }

        $similarities['matching_components'] = $matchingComponents;
        $similarities['total_components'] = $totalComponents;
        $similarities['component_match_percentage'] = $totalComponents > 0
            ? round(($matchingComponents / $totalComponents) * 100, 2)
            : 0;
        $similarities['occurrence_match_percentage'] = $totalOccurrences > 0
            ? round(($matchingOccurrences / $totalOccurrences) * 100, 2)
            : 0;

        return $similarities;
    }

    /**
     * Benchmark performance comparison between Rruler and sabre/vobject.
     *
     * @param string $icalContent The iCalendar content to benchmark
     * @param int $iterations Number of iterations to run for averaging
     * @return array Performance benchmark results
     */
    /**
     * @return array<string, mixed>
     */
    public function benchmarkPerformance(string $icalContent, int $iterations = 5): array
    {
        $results = [
            'rruler_times' => [],
            'sabre_times' => [],
            'rruler_memory' => [],
            'sabre_memory' => [],
        ];

        // Benchmark Rruler
        for ($i = 0; $i < $iterations; ++$i) {
            $memoryBefore = memory_get_usage();
            $startTime = microtime(true);

            try {
                $this->rrulerIcalParser->parse($icalContent);
                $endTime = microtime(true);
                $memoryAfter = memory_get_usage();

                $results['rruler_times'][] = $endTime - $startTime;
                $results['rruler_memory'][] = $memoryAfter - $memoryBefore;
            } catch (\Exception $e) {
                $results['rruler_times'][] = -1; // Error marker
                $results['rruler_memory'][] = 0;
            }
        }

        // Benchmark sabre/vobject
        for ($i = 0; $i < $iterations; ++$i) {
            $memoryBefore = memory_get_usage();
            $startTime = microtime(true);

            try {
                Reader::read($icalContent);
                $endTime = microtime(true);
                $memoryAfter = memory_get_usage();

                $results['sabre_times'][] = $endTime - $startTime;
                $results['sabre_memory'][] = $memoryAfter - $memoryBefore;
            } catch (\Exception $e) {
                $results['sabre_times'][] = -1; // Error marker
                $results['sabre_memory'][] = 0;
            }
        }

        // Calculate averages (excluding errors)
        $validRrulerTimes = array_filter($results['rruler_times'], fn ($time) => $time >= 0);
        $validSabreTimes = array_filter($results['sabre_times'], fn ($time) => $time >= 0);

        $results['rruler_avg_time'] = !empty($validRrulerTimes)
            ? array_sum($validRrulerTimes) / count($validRrulerTimes)
            : -1;

        $results['sabre_avg_time'] = !empty($validSabreTimes)
            ? array_sum($validSabreTimes) / count($validSabreTimes)
            : -1;

        $validRrulerMemory = array_filter($results['rruler_memory'], fn ($mem) => $mem > 0);
        $validSabreMemory = array_filter($results['sabre_memory'], fn ($mem) => $mem > 0);

        $results['rruler_memory_usage'] = !empty($validRrulerMemory)
            ? array_sum($validRrulerMemory) / count($validRrulerMemory)
            : 0;

        $results['sabre_memory_usage'] = !empty($validSabreMemory)
            ? array_sum($validSabreMemory) / count($validSabreMemory)
            : 0;

        return $results;
    }
}
