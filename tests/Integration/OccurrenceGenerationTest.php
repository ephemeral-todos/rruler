<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceValidator;
use EphemeralTodos\Rruler\Rrule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class OccurrenceGenerationTest extends TestCase
{
    private DefaultOccurrenceGenerator $generator;
    private DefaultOccurrenceValidator $validator;

    protected function setUp(): void
    {
        $this->generator = new DefaultOccurrenceGenerator();
        $this->validator = new DefaultOccurrenceValidator($this->generator);
    }

    #[DataProvider('provideEndToEndScenarios')]
    public function testEndToEndOccurrenceGeneration(
        string $rruleString,
        string $startDate,
        array $expectedOccurrences,
        array $validCandidates,
        array $invalidCandidates,
    ): void {
        // Parse RRULE
        $rrule = Rrule::fromString($rruleString);
        $start = new DateTimeImmutable($startDate);

        // Generate occurrences
        $occurrences = $this->generator->generateOccurrences($rrule, $start);
        $results = iterator_to_array($occurrences);

        // Verify expected occurrences
        $this->assertCount(count($expectedOccurrences), $results);
        foreach ($expectedOccurrences as $index => $expectedDate) {
            $this->assertEquals(
                new DateTimeImmutable($expectedDate),
                $results[$index],
                "Occurrence {$index} should match expected date"
            );
        }

        // Validate each generated occurrence using validator
        foreach ($results as $occurrence) {
            $this->assertTrue(
                $this->validator->isValidOccurrence($rrule, $start, $occurrence),
                'Generated occurrence should be valid: '.$occurrence->format('Y-m-d')
            );
        }

        // Test valid candidates
        foreach ($validCandidates as $candidateDate) {
            $candidate = new DateTimeImmutable($candidateDate);
            $this->assertTrue(
                $this->validator->isValidOccurrence($rrule, $start, $candidate),
                "Candidate should be valid: {$candidateDate}"
            );
        }

        // Test invalid candidates
        foreach ($invalidCandidates as $candidateDate) {
            $candidate = new DateTimeImmutable($candidateDate);
            $this->assertFalse(
                $this->validator->isValidOccurrence($rrule, $start, $candidate),
                "Candidate should be invalid: {$candidateDate}"
            );
        }
    }

    public function testGeneratorAndValidatorConsistency(): void
    {
        $scenarios = [
            ['FREQ=DAILY;COUNT=10', '2025-01-01'],
            ['FREQ=DAILY;INTERVAL=3;COUNT=5', '2025-01-01'],
            ['FREQ=WEEKLY;COUNT=8', '2025-01-01'],
            ['FREQ=WEEKLY;INTERVAL=2;COUNT=4', '2025-01-01'],
            ['FREQ=DAILY;UNTIL=20250110T235959Z', '2025-01-01'],
            ['FREQ=WEEKLY;UNTIL=20250301T235959Z', '2025-01-01'],
        ];

        foreach ($scenarios as [$rruleString, $startDate]) {
            $rrule = Rrule::fromString($rruleString);
            $start = new DateTimeImmutable($startDate);

            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start));

            foreach ($occurrences as $occurrence) {
                $this->assertTrue(
                    $this->validator->isValidOccurrence($rrule, $start, $occurrence),
                    "All generated occurrences should validate as true for: {$rruleString}"
                );
            }
        }
    }

    public function testDateRangeFiltering(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY');
        $start = new DateTimeImmutable('2025-01-01');
        $rangeStart = new DateTimeImmutable('2025-01-05');
        $rangeEnd = new DateTimeImmutable('2025-01-10');

        $occurrences = $this->generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $results = iterator_to_array($occurrences);

        $this->assertCount(6, $results); // 2025-01-05 through 2025-01-10
        $this->assertEquals(new DateTimeImmutable('2025-01-05'), $results[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-10'), $results[5]);

        // Verify all results are within range
        foreach ($results as $occurrence) {
            $this->assertGreaterThanOrEqual($rangeStart, $occurrence);
            $this->assertLessThanOrEqual($rangeEnd, $occurrence);
        }
    }

    public function testComplexCountUntilScenarios(): void
    {
        // Test COUNT limiting occurrences
        $rruleWithCount = Rrule::fromString('FREQ=DAILY;COUNT=3');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleWithCount, $start));

        $this->assertCount(3, $occurrences); // COUNT=3 should limit
        $this->assertEquals(new DateTimeImmutable('2025-01-01'), $occurrences[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-02'), $occurrences[1]);
        $this->assertEquals(new DateTimeImmutable('2025-01-03'), $occurrences[2]);

        // Test UNTIL limiting occurrences
        $rruleWithUntil = Rrule::fromString('FREQ=DAILY;UNTIL=20250103T235959Z');
        $occurrences = iterator_to_array($this->generator->generateOccurrences($rruleWithUntil, $start));

        $this->assertCount(3, $occurrences); // UNTIL should limit to 3 days
        $this->assertEquals(new DateTimeImmutable('2025-01-01'), $occurrences[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-02'), $occurrences[1]);
        $this->assertEquals(new DateTimeImmutable('2025-01-03'), $occurrences[2]);
    }

    public function testPerformanceWithLargeOccurrenceSets(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY;COUNT=1000');
        $start = new DateTimeImmutable('2025-01-01');

        $startTime = microtime(true);

        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start) as $occurrence) {
            ++$count;
            // Just iterate, don't collect all in memory
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(1000, $count);
        $this->assertLessThan(1.0, $executionTime, 'Should generate 1000 occurrences in less than 1 second');
    }

    public static function provideEndToEndScenarios(): array
    {
        return [
            'daily with count' => [
                'FREQ=DAILY;COUNT=3',
                '2025-01-01',
                ['2025-01-01', '2025-01-02', '2025-01-03'],
                ['2025-01-01', '2025-01-02', '2025-01-03'],
                ['2024-12-31', '2025-01-04', '2025-01-05'],
            ],
            'daily with interval' => [
                'FREQ=DAILY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                ['2025-01-01', '2025-01-03', '2025-01-05'],
                ['2025-01-01', '2025-01-03', '2025-01-05'],
                ['2025-01-02', '2025-01-04', '2025-01-06'],
            ],
            'weekly with count' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01', // Wednesday
                ['2025-01-01', '2025-01-08', '2025-01-15'],
                ['2025-01-01', '2025-01-08', '2025-01-15'],
                ['2025-01-02', '2025-01-07', '2025-01-09', '2025-01-22'],
            ],
            'weekly with interval' => [
                'FREQ=WEEKLY;INTERVAL=2;COUNT=3',
                '2025-01-01', // Wednesday
                ['2025-01-01', '2025-01-15', '2025-01-29'],
                ['2025-01-01', '2025-01-15', '2025-01-29'],
                ['2025-01-08', '2025-01-22', '2025-02-05'],
            ],
            'daily with until' => [
                'FREQ=DAILY;UNTIL=20250103T235959Z',
                '2025-01-01',
                ['2025-01-01', '2025-01-02', '2025-01-03'],
                ['2025-01-01', '2025-01-02', '2025-01-03'],
                ['2024-12-31', '2025-01-04', '2025-01-05'],
            ],
        ];
    }
}
