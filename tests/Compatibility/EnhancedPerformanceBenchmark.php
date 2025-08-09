<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use Sabre\VObject\Reader;

/**
 * Performance benchmarking framework for comparing Rruler and sabre/vobject performance.
 */
final class EnhancedPerformanceBenchmark
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
     * Run comprehensive performance benchmarks comparing Rruler and sabre/vobject.
     *
     * @param array $testFiles Array of iCalendar file paths to benchmark
     * @param int $iterations Number of iterations to run for each test
     * @return array Benchmark results with timing and memory usage data
     */
    public function runBenchmarks(array $testFiles, int $iterations = 10): array
    {
        $results = [
            'test_configuration' => [
                'iterations' => $iterations,
                'test_files_count' => count($testFiles),
                'php_version' => PHP_VERSION,
                'memory_limit' => ini_get('memory_limit'),
            ],
            'parsing_benchmarks' => [],
            'occurrence_benchmarks' => [],
            'memory_benchmarks' => [],
            'comparative_analysis' => [],
        ];

        foreach ($testFiles as $index => $filePath) {
            $testName = 'test_'.($index + 1).'_'.basename($filePath, '.ics');
            $icalContent = file_get_contents($filePath);

            if ($icalContent === false) {
                continue;
            }

            // Run parsing benchmarks
            $results['parsing_benchmarks'][$testName] = $this->benchmarkParsing($icalContent, $iterations);

            // Run occurrence generation benchmarks
            $results['occurrence_benchmarks'][$testName] = $this->benchmarkOccurrenceGeneration($icalContent, $iterations);

            // Run memory usage benchmarks
            $results['memory_benchmarks'][$testName] = $this->benchmarkMemoryUsage($icalContent);
        }

        // Generate comparative analysis
        $results['comparative_analysis'] = $this->generateComparativeAnalysis($results);

        return $results;
    }

    /**
     * Benchmark parsing performance between Rruler and sabre/vobject.
     */
    private function benchmarkParsing(string $icalContent, int $iterations): array
    {
        $rrulerTimes = [];
        $sabreTimes = [];

        // Benchmark Rruler parsing
        for ($i = 0; $i < $iterations; ++$i) {
            $startTime = microtime(true);
            try {
                $this->rrulerIcalParser->parse($icalContent);
            } catch (\Exception $e) {
                // Record error but continue
            }
            $endTime = microtime(true);
            $rrulerTimes[] = $endTime - $startTime;
        }

        // Benchmark sabre/vobject parsing
        for ($i = 0; $i < $iterations; ++$i) {
            $startTime = microtime(true);
            try {
                Reader::read($icalContent);
            } catch (\Exception $e) {
                // Record error but continue
            }
            $endTime = microtime(true);
            $sabreTimes[] = $endTime - $startTime;
        }

        return [
            'rruler' => [
                'avg_time' => array_sum($rrulerTimes) / count($rrulerTimes),
                'min_time' => min($rrulerTimes),
                'max_time' => max($rrulerTimes),
                'total_time' => array_sum($rrulerTimes),
                'iterations' => count($rrulerTimes),
            ],
            'sabre' => [
                'avg_time' => array_sum($sabreTimes) / count($sabreTimes),
                'min_time' => min($sabreTimes),
                'max_time' => max($sabreTimes),
                'total_time' => array_sum($sabreTimes),
                'iterations' => count($sabreTimes),
            ],
            'comparison' => [
                'rruler_vs_sabre_ratio' => count($sabreTimes) > 0 && array_sum($sabreTimes) > 0
                    ? array_sum($rrulerTimes) / array_sum($sabreTimes)
                    : 0,
                'performance_winner' => array_sum($rrulerTimes) < array_sum($sabreTimes) ? 'rruler' : 'sabre',
            ],
        ];
    }

    /**
     * Benchmark occurrence generation performance.
     */
    private function benchmarkOccurrenceGeneration(string $icalContent, int $iterations): array
    {
        $rrulerTimes = [];
        $sabreTimes = [];
        $maxOccurrences = 50;

        // Benchmark Rruler occurrence generation
        for ($i = 0; $i < $iterations; ++$i) {
            $startTime = microtime(true);
            try {
                $components = $this->rrulerIcalParser->parse($icalContent);
                foreach ($components as $component) {
                    if (isset($component['rrule']) && isset($component['dateTimeContext'])) {
                        $count = 0;
                        foreach ($this->occurrenceGenerator->generateOccurrences(
                            $component['rrule'],
                            $component['dateTimeContext']->getDateTime(),
                            $maxOccurrences
                        ) as $occurrence) {
                            ++$count;
                            if ($count >= $maxOccurrences) {
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Record error but continue
            }
            $endTime = microtime(true);
            $rrulerTimes[] = $endTime - $startTime;
        }

        // Benchmark sabre/vobject occurrence generation
        for ($i = 0; $i < $iterations; ++$i) {
            $startTime = microtime(true);
            try {
                $calendar = Reader::read($icalContent);
                foreach ($calendar->getComponents() as $component) {
                    if (($component instanceof \Sabre\VObject\Component\VEvent
                         || $component instanceof \Sabre\VObject\Component\VTodo)
                        && isset($component->RRULE)) {
                        $iterator = new \Sabre\VObject\Recur\EventIterator($calendar, $component->UID);
                        $count = 0;
                        while ($iterator->valid() && $count < $maxOccurrences) {
                            $iterator->current();
                            $iterator->next();
                            ++$count;
                        }
                    }
                }
            } catch (\Exception $e) {
                // Record error but continue
            }
            $endTime = microtime(true);
            $sabreTimes[] = $endTime - $startTime;
        }

        return [
            'rruler' => [
                'avg_time' => count($rrulerTimes) > 0 ? array_sum($rrulerTimes) / count($rrulerTimes) : 0,
                'min_time' => count($rrulerTimes) > 0 ? min($rrulerTimes) : 0,
                'max_time' => count($rrulerTimes) > 0 ? max($rrulerTimes) : 0,
                'total_time' => array_sum($rrulerTimes),
                'iterations' => count($rrulerTimes),
            ],
            'sabre' => [
                'avg_time' => count($sabreTimes) > 0 ? array_sum($sabreTimes) / count($sabreTimes) : 0,
                'min_time' => count($sabreTimes) > 0 ? min($sabreTimes) : 0,
                'max_time' => count($sabreTimes) > 0 ? max($sabreTimes) : 0,
                'total_time' => array_sum($sabreTimes),
                'iterations' => count($sabreTimes),
            ],
            'comparison' => [
                'rruler_vs_sabre_ratio' => count($sabreTimes) > 0 && array_sum($sabreTimes) > 0
                    ? array_sum($rrulerTimes) / array_sum($sabreTimes)
                    : 0,
                'performance_winner' => array_sum($rrulerTimes) < array_sum($sabreTimes) ? 'rruler' : 'sabre',
            ],
        ];
    }

    /**
     * Benchmark memory usage for both libraries.
     */
    private function benchmarkMemoryUsage(string $icalContent): array
    {
        // Measure Rruler memory usage
        $baseMemory = memory_get_usage(true);
        try {
            $components = $this->rrulerIcalParser->parse($icalContent);
            $rrulerMemoryPeak = memory_get_peak_usage(true) - $baseMemory;
            $rrulerMemoryUsage = memory_get_usage(true) - $baseMemory;
        } catch (\Exception $e) {
            $rrulerMemoryPeak = 0;
            $rrulerMemoryUsage = 0;
        }

        // Clear memory and measure sabre/vobject usage
        gc_collect_cycles();
        $baseMemory = memory_get_usage(true);
        try {
            $calendar = Reader::read($icalContent);
            $sabreMemoryPeak = memory_get_peak_usage(true) - $baseMemory;
            $sabreMemoryUsage = memory_get_usage(true) - $baseMemory;
        } catch (\Exception $e) {
            $sabreMemoryPeak = 0;
            $sabreMemoryUsage = 0;
        }

        return [
            'rruler' => [
                'peak_memory_bytes' => $rrulerMemoryPeak,
                'peak_memory_mb' => round($rrulerMemoryPeak / 1024 / 1024, 2),
                'current_memory_bytes' => $rrulerMemoryUsage,
                'current_memory_mb' => round($rrulerMemoryUsage / 1024 / 1024, 2),
            ],
            'sabre' => [
                'peak_memory_bytes' => $sabreMemoryPeak,
                'peak_memory_mb' => round($sabreMemoryPeak / 1024 / 1024, 2),
                'current_memory_bytes' => $sabreMemoryUsage,
                'current_memory_mb' => round($sabreMemoryUsage / 1024 / 1024, 2),
            ],
            'comparison' => [
                'rruler_vs_sabre_peak_ratio' => $sabreMemoryPeak > 0 ? $rrulerMemoryPeak / $sabreMemoryPeak : 0,
                'memory_winner' => $rrulerMemoryPeak < $sabreMemoryPeak ? 'rruler' : 'sabre',
            ],
        ];
    }

    /**
     * Generate comparative analysis across all benchmark results.
     */
    private function generateComparativeAnalysis(array $results): array
    {
        $analysis = [
            'overall_performance' => [],
            'parsing_performance' => [],
            'occurrence_performance' => [],
            'memory_efficiency' => [],
            'recommendations' => [],
        ];

        $parsingWins = ['rruler' => 0, 'sabre' => 0];
        $occurrenceWins = ['rruler' => 0, 'sabre' => 0];
        $memoryWins = ['rruler' => 0, 'sabre' => 0];

        // Analyze parsing performance
        foreach ($results['parsing_benchmarks'] as $testResult) {
            $winner = $testResult['comparison']['performance_winner'];
            ++$parsingWins[$winner];
        }

        // Analyze occurrence generation performance
        foreach ($results['occurrence_benchmarks'] as $testResult) {
            $winner = $testResult['comparison']['performance_winner'];
            ++$occurrenceWins[$winner];
        }

        // Analyze memory efficiency
        foreach ($results['memory_benchmarks'] as $testResult) {
            $winner = $testResult['comparison']['memory_winner'];
            ++$memoryWins[$winner];
        }

        $analysis['parsing_performance'] = [
            'rruler_wins' => $parsingWins['rruler'],
            'sabre_wins' => $parsingWins['sabre'],
            'overall_winner' => $parsingWins['rruler'] > $parsingWins['sabre'] ? 'rruler' : 'sabre',
        ];

        $analysis['occurrence_performance'] = [
            'rruler_wins' => $occurrenceWins['rruler'],
            'sabre_wins' => $occurrenceWins['sabre'],
            'overall_winner' => $occurrenceWins['rruler'] > $occurrenceWins['sabre'] ? 'rruler' : 'sabre',
        ];

        $analysis['memory_efficiency'] = [
            'rruler_wins' => $memoryWins['rruler'],
            'sabre_wins' => $memoryWins['sabre'],
            'overall_winner' => $memoryWins['rruler'] > $memoryWins['sabre'] ? 'rruler' : 'sabre',
        ];

        // Overall performance assessment
        $rrulerTotalWins = $parsingWins['rruler'] + $occurrenceWins['rruler'] + $memoryWins['rruler'];
        $sabreTotalWins = $parsingWins['sabre'] + $occurrenceWins['sabre'] + $memoryWins['sabre'];

        $analysis['overall_performance'] = [
            'rruler_total_wins' => $rrulerTotalWins,
            'sabre_total_wins' => $sabreTotalWins,
            'overall_winner' => $rrulerTotalWins > $sabreTotalWins ? 'rruler' : 'sabre',
            'performance_summary' => $rrulerTotalWins > $sabreTotalWins
                ? 'Rruler demonstrates superior performance across test scenarios'
                : 'sabre/vobject demonstrates superior performance across test scenarios',
        ];

        // Generate recommendations
        $analysis['recommendations'] = $this->generatePerformanceRecommendations($analysis);

        return $analysis;
    }

    private function generatePerformanceRecommendations(array $analysis): array
    {
        $recommendations = [];

        if ($analysis['overall_performance']['overall_winner'] === 'rruler') {
            $recommendations[] = 'Rruler shows strong performance characteristics suitable for production use';
            $recommendations[] = 'Consider using Rruler for performance-critical iCalendar processing';
        } else {
            $recommendations[] = 'Investigate performance optimization opportunities in Rruler';
            $recommendations[] = 'Focus on parsing efficiency improvements';
        }

        if ($analysis['memory_efficiency']['overall_winner'] === 'rruler') {
            $recommendations[] = 'Rruler demonstrates better memory efficiency for large iCalendar files';
        } else {
            $recommendations[] = 'Consider memory usage optimization for large file processing';
        }

        $recommendations[] = 'Continuous benchmarking recommended to track performance over time';
        $recommendations[] = 'Consider specific use case performance testing for production scenarios';

        return $recommendations;
    }
}
