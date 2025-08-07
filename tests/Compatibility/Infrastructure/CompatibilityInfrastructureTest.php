<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility\Infrastructure;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Tests\Compatibility\CompatibilityTestCase;
use EphemeralTodos\Rruler\Tests\Compatibility\ResultComparator;
use EphemeralTodos\Rruler\Tests\Compatibility\RrulePatternGenerator;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests for the compatibility testing infrastructure itself.
 * Ensures that our testing framework works correctly before using it
 * to validate compatibility between Rruler and sabre/vobject.
 */
final class CompatibilityInfrastructureTest extends CompatibilityTestCase
{
    public function testCompatibilityTestCaseSetup(): void
    {
        $this->assertInstanceOf(\EphemeralTodos\Rruler\Rruler::class, $this->rruler);
        $this->assertInstanceOf(\EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator::class, $this->occurrenceGenerator);
    }

    public function testRrulerOccurrenceGeneration(): void
    {
        $rruleString = 'FREQ=DAILY;COUNT=3';
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->getRrulerOccurrences($rruleString, $start);

        $this->assertCount(3, $occurrences);
        $this->assertEquals(new DateTimeImmutable('2025-01-01'), $occurrences[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-02'), $occurrences[1]);
        $this->assertEquals(new DateTimeImmutable('2025-01-03'), $occurrences[2]);
    }

    public function testSabreOccurrenceGeneration(): void
    {
        $rruleString = 'FREQ=DAILY;COUNT=3';
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->getSabreOccurrences($rruleString, $start);

        $this->assertCount(3, $occurrences);
        // Allow for potential timezone differences in comparison
        $this->assertEquals('2025-01-01', $occurrences[0]->format('Y-m-d'));
        $this->assertEquals('2025-01-02', $occurrences[1]->format('Y-m-d'));
        $this->assertEquals('2025-01-03', $occurrences[2]->format('Y-m-d'));
    }

    public function testSimpleCompatibilityAssertion(): void
    {
        $rruleString = 'FREQ=DAILY;COUNT=3';
        $start = new DateTimeImmutable('2025-01-01');

        // This should not throw an exception if compatibility testing works
        $this->assertRruleCompatibility($rruleString, $start, 3, 'Simple daily pattern');
    }

    public function testResultComparatorMatching(): void
    {
        $occurrences1 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-01-03'),
        ];

        $occurrences2 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-01-03'),
        ];

        $result = ResultComparator::compareOccurrences($occurrences1, $occurrences2);

        $this->assertTrue($result->matches());
        $this->assertEmpty($result->getMismatches());
    }

    public function testResultComparatorMismatch(): void
    {
        $occurrences1 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
        ];

        $occurrences2 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-03'), // Different second date
        ];

        $result = ResultComparator::compareOccurrences($occurrences1, $occurrences2);

        $this->assertFalse($result->matches());
        $this->assertNotEmpty($result->getMismatches());
        $this->assertArrayHasKey('occurrence_1', $result->getMismatches());
    }

    public function testResultComparatorCountMismatch(): void
    {
        $occurrences1 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
        ];

        $occurrences2 = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-01-03'),
        ];

        $result = ResultComparator::compareOccurrences($occurrences1, $occurrences2);

        $this->assertFalse($result->matches());
        $this->assertArrayHasKey('count', $result->getMismatches());
        $this->assertArrayHasKey('occurrence_2', $result->getMismatches());
    }

    public function testOccurrenceFormatting(): void
    {
        $occurrences = [
            new DateTimeImmutable('2025-01-01 10:30:00'),
            new DateTimeImmutable('2025-01-02 15:45:30'),
        ];

        $formatted = $this->formatOccurrences($occurrences);

        $this->assertStringContainsString('2025-01-01 10:30:00', $formatted);
        $this->assertStringContainsString('2025-01-02 15:45:30', $formatted);
    }

    public function testPatternGeneratorBasicFrequencies(): void
    {
        $patterns = RrulePatternGenerator::generateBasicFrequencyPatterns();

        $this->assertNotEmpty($patterns);

        // Verify pattern structure
        foreach ($patterns as $pattern) {
            $this->assertArrayHasKey('rrule', $pattern);
            $this->assertArrayHasKey('start', $pattern);
            $this->assertArrayHasKey('description', $pattern);
            $this->assertStringStartsWith('FREQ=', $pattern['rrule']);
        }

        // Verify we have patterns for all frequencies
        $frequencies = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
        foreach ($frequencies as $freq) {
            $hasFreq = false;
            foreach ($patterns as $pattern) {
                if (str_contains($pattern['rrule'], "FREQ={$freq}")) {
                    $hasFreq = true;
                    break;
                }
            }
            $this->assertTrue($hasFreq, "Missing patterns for frequency {$freq}");
        }
    }

    #[DataProvider('provideSamplePatterns')]
    public function testSamplePatternsWork(string $rrule, string $start, string $description): void
    {
        $startDate = new DateTimeImmutable($start);

        // Test that both Rruler and sabre can handle these patterns without errors
        $rrulerOccurrences = $this->getRrulerOccurrences($rrule, $startDate, 5);
        $sabreOccurrences = $this->getSabreOccurrences($rrule, $startDate, 5);

        $this->assertNotEmpty($rrulerOccurrences, "Rruler should generate occurrences for: {$description}");
        $this->assertNotEmpty($sabreOccurrences, "Sabre should generate occurrences for: {$description}");
    }

    public static function provideSamplePatterns(): array
    {
        return [
            ['FREQ=DAILY;COUNT=3', '2025-01-01', 'Simple daily'],
            ['FREQ=WEEKLY;COUNT=3', '2025-01-01', 'Simple weekly'],
            ['FREQ=MONTHLY;COUNT=3', '2025-01-01', 'Simple monthly'],
            ['FREQ=YEARLY;COUNT=3', '2025-01-01', 'Simple yearly'],
            ['FREQ=WEEKLY;BYDAY=MO;COUNT=3', '2025-01-01', 'Weekly Monday'],
            ['FREQ=MONTHLY;BYMONTHDAY=15;COUNT=3', '2025-01-01', 'Monthly 15th'],
        ];
    }

    public function testReportGeneration(): void
    {
        $rrule = 'FREQ=DAILY;COUNT=3';
        $start = new DateTimeImmutable('2025-01-01');
        $expected = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-01-03'),
        ];
        $actual = [
            new DateTimeImmutable('2025-01-01'),
            new DateTimeImmutable('2025-01-02'),
            new DateTimeImmutable('2025-01-03'),
        ];

        $report = ResultComparator::generateReport($rrule, $start, $expected, $actual, 'Test report');

        $this->assertStringContainsString('COMPATIBILITY TEST REPORT', $report);
        $this->assertStringContainsString('PERFECT MATCH', $report);
        $this->assertStringContainsString($rrule, $report);
        $this->assertStringContainsString('Test report', $report);
    }
}
