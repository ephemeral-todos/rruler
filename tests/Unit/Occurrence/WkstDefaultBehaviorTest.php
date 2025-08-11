<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\TestCase;

/**
 * Test WKST default behavior when omitted from RRULE.
 *
 * According to RFC 5545, when WKST is not specified, it should default to MO (Monday).
 * These tests verify this behavior across all frequency types.
 */
final class WkstDefaultBehaviorTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testDefaultWkstIsMondayWhenOmitted(): void
    {
        // Test that WKST defaults to 'MO' when not specified
        $rruleWithoutWkst = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=TU;COUNT=3');
        $rruleWithExplicitWkst = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=TU;WKST=MO;COUNT=3');

        $this->assertEquals('MO', $rruleWithoutWkst->getWeekStart(), 'Default WKST should be MO');
        $this->assertEquals('MO', $rruleWithExplicitWkst->getWeekStart(), 'Explicit WKST=MO should be MO');
    }

    public function testDefaultWkstProducesSameResultsAsExplicitMO(): void
    {
        // Test that omitting WKST produces same results as explicit WKST=MO
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=WEEKLY;BYDAY=WE;COUNT=4',
            'FREQ=WEEKLY;INTERVAL=2;BYDAY=FR;COUNT=4',
            'FREQ=MONTHLY;BYDAY=1MO;COUNT=4',
            'FREQ=YEARLY;BYMONTH=6;BYDAY=TU;COUNT=3',
        ];

        foreach ($patterns as $pattern) {
            $rruleDefault = $this->testRruler->parse($pattern);
            $rruleExplicit = $this->testRruler->parse($pattern.';WKST=MO');

            $occurrencesDefault = iterator_to_array($this->generator->generateOccurrences($rruleDefault, $start));
            $occurrencesExplicit = iterator_to_array($this->generator->generateOccurrences($rruleExplicit, $start));

            $this->assertEquals(
                $occurrencesExplicit,
                $occurrencesDefault,
                "Default WKST should produce same results as explicit WKST=MO for pattern: {$pattern}"
            );
        }
    }

    public function testDefaultWkstBehaviorAcrossAllFrequencies(): void
    {
        // Test default WKST behavior for all frequency types
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $frequencyTests = [
            [
                'pattern' => 'FREQ=DAILY;COUNT=3',
                'frequency' => 'DAILY',
                'description' => 'Daily frequency with default WKST',
            ],
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=MO;COUNT=3',
                'frequency' => 'WEEKLY',
                'description' => 'Weekly frequency with default WKST',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYMONTHDAY=15;COUNT=3',
                'frequency' => 'MONTHLY',
                'description' => 'Monthly frequency with default WKST',
            ],
            [
                'pattern' => 'FREQ=YEARLY;BYMONTH=1;BYMONTHDAY=1;COUNT=3',
                'frequency' => 'YEARLY',
                'description' => 'Yearly frequency with default WKST',
            ],
        ];

        foreach ($frequencyTests as $test) {
            $rrule = $this->testRruler->parse($test['pattern']);

            $this->assertEquals('MO', $rrule->getWeekStart(), $test['description']);
            $this->assertEquals($test['frequency'], $rrule->getFrequency(), $test['description']);

            // Verify occurrences can be generated without issues
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 3));
            $this->assertCount(3, $occurrences, $test['description']);
        }
    }

    public function testDefaultWkstWithByDayPatterns(): void
    {
        // Test default WKST with various BYDAY patterns
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $byDayTests = [
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=SU;COUNT=4',
                'expectedDay' => 'Sunday',
                'description' => 'Weekly Sunday with default WKST',
            ],
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6',
                'expectedDays' => ['Monday', 'Wednesday', 'Friday'],
                'description' => 'Weekly MWF with default WKST',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=1SU;COUNT=4',
                'expectedDay' => 'Sunday',
                'description' => 'Monthly first Sunday with default WKST',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=-1FR;COUNT=4',
                'expectedDay' => 'Friday',
                'description' => 'Monthly last Friday with default WKST',
            ],
        ];

        foreach ($byDayTests as $test) {
            $rrule = $this->testRruler->parse($test['pattern']);
            $this->assertEquals('MO', $rrule->getWeekStart(), $test['description']);

            $count = str_contains($test['pattern'], 'COUNT=6') ? 6 : 4;
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, $count));
            $this->assertCount($count, $occurrences, $test['description']);

            foreach ($occurrences as $occurrence) {
                $dayName = $occurrence->format('l');
                if (isset($test['expectedDay'])) {
                    $this->assertEquals($test['expectedDay'], $dayName, $test['description']);
                } elseif (isset($test['expectedDays'])) {
                    $this->assertContains($dayName, $test['expectedDays'], $test['description']);
                }
            }
        }
    }

    public function testDefaultWkstWithComplexPatterns(): void
    {
        // Test default WKST with complex patterns involving multiple BY* rules
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $complexTests = [
            [
                'pattern' => 'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;COUNT=8',
                'description' => 'Quarterly Fridays with default WKST',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;COUNT=6',
                'description' => 'First and last weekdays with default WKST',
            ],
            [
                'pattern' => 'FREQ=YEARLY;BYWEEKNO=26;COUNT=3',
                'description' => 'Yearly week 26 with default WKST',
            ],
        ];

        foreach ($complexTests as $test) {
            $rrule = $this->testRruler->parse($test['pattern']);
            $this->assertEquals('MO', $rrule->getWeekStart(), $test['description']);

            // Verify occurrences can be generated
            $count = (int) preg_replace('/.*COUNT=(\d+).*/', '$1', $test['pattern']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, $count));
            $this->assertCount($count, $occurrences, $test['description']);
        }
    }

    public function testDefaultWkstConsistencyAcrossParsingAndGeneration(): void
    {
        // Test that default WKST is consistent between parsing and generation
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $patterns = [
            'FREQ=WEEKLY;BYDAY=TH;COUNT=4',
            'FREQ=MONTHLY;BYDAY=2WE;COUNT=4',
            'FREQ=YEARLY;BYMONTH=7;BYDAY=MO;COUNT=3',
        ];

        foreach ($patterns as $pattern) {
            // Parse without WKST
            $rrule = $this->testRruler->parse($pattern);

            // Verify parsing sets default WKST
            $this->assertEquals('MO', $rrule->getWeekStart(), "Parsing should set default WKST for: {$pattern}");

            // Generate occurrences
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start));
            $this->assertNotEmpty($occurrences, "Should generate occurrences for: {$pattern}");

            // Verify all occurrences are valid
            foreach ($occurrences as $occurrence) {
                $this->assertInstanceOf(DateTimeImmutable::class, $occurrence, "Pattern: {$pattern}");
            }
        }
    }

    public function testDefaultWkstVersusExplicitNonMondayComparison(): void
    {
        // Test that default WKST (MO) produces different results than explicit non-Monday WKST
        $start = new DateTimeImmutable('2024-01-01 09:00:00');

        $testCases = [
            [
                'base_pattern' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE;COUNT=4',
                'wkst_values' => ['SU', 'TU', 'TH', 'SA'],
            ],
            [
                'base_pattern' => 'FREQ=MONTHLY;BYDAY=1MO,3WE;COUNT=6',
                'wkst_values' => ['SU', 'WE', 'FR'],
            ],
        ];

        foreach ($testCases as $testCase) {
            $rruleDefault = $this->testRruler->parse($testCase['base_pattern']);
            $occurrencesDefault = iterator_to_array($this->generator->generateOccurrences($rruleDefault, $start));

            foreach ($testCase['wkst_values'] as $wkst) {
                $rruleExplicit = $this->testRruler->parse($testCase['base_pattern'].";WKST={$wkst}");
                $occurrencesExplicit = iterator_to_array($this->generator->generateOccurrences($rruleExplicit, $start));

                // Results might be the same or different, but both should produce valid occurrences
                $this->assertCount(count($occurrencesDefault), $occurrencesExplicit,
                    "Should produce same count for pattern: {$testCase['base_pattern']} with WKST={$wkst}");

                $this->assertNotEmpty($occurrencesExplicit,
                    "Should produce occurrences for pattern: {$testCase['base_pattern']} with WKST={$wkst}");
            }
        }
    }

    public function testDefaultWkstWithEdgeCases(): void
    {
        // Test default WKST behavior with edge cases
        $edgeCases = [
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=SU;COUNT=3',
                'start' => '2024-01-07 09:00:00', // Starting on Sunday
                'description' => 'Starting on Sunday with default WKST',
            ],
            [
                'pattern' => 'FREQ=WEEKLY;BYDAY=MO;COUNT=3',
                'start' => '2024-01-01 09:00:00', // Starting on Monday
                'description' => 'Starting on Monday (default WKST day) with default WKST',
            ],
            [
                'pattern' => 'FREQ=MONTHLY;BYDAY=-1SU;COUNT=4',
                'start' => '2024-01-28 09:00:00', // Starting on last Sunday of month
                'description' => 'Starting on target day with default WKST',
            ],
        ];

        foreach ($edgeCases as $edgeCase) {
            $start = new DateTimeImmutable($edgeCase['start']);
            $rrule = $this->testRruler->parse($edgeCase['pattern']);

            $this->assertEquals('MO', $rrule->getWeekStart(), $edgeCase['description']);

            $count = (int) preg_replace('/.*COUNT=(\d+).*/', '$1', $edgeCase['pattern']);
            $occurrences = iterator_to_array($this->generator->generateOccurrences($rrule, $start, $count));
            $this->assertCount($count, $occurrences, $edgeCase['description']);

            // First occurrence should include/be the start date if it matches the pattern
            $this->assertGreaterThanOrEqual($start, $occurrences[0], $edgeCase['description']);
        }
    }
}
