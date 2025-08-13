<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Testing\Utilities\RrulePatternGenerator;

/**
 * Basic frequency pattern compatibility tests between Rruler and sabre/vobject.
 *
 * Tests FREQ parameters (DAILY, WEEKLY, MONTHLY, YEARLY) with various INTERVAL
 * values and termination conditions (COUNT, UNTIL).
 */
final class BasicFrequencyCompatibilityTest extends CompatibilityTestCase
{
    /**
     * @dataProvider basicFrequencyPatternProvider
     */
    public function testBasicFrequencyPatterns(array $pattern): void
    {
        $start = new DateTimeImmutable($pattern['start']);

        $this->assertRruleCompatibility(
            $pattern['rrule'],
            $start,
            10, // Test first 10 occurrences
            $pattern['description']
        );
    }

    /**
     * @dataProvider countPatternProvider
     */
    public function testCountTermination(array $pattern): void
    {
        $start = new DateTimeImmutable($pattern['start']);

        // For count patterns, use the count from the RRULE itself
        preg_match('/COUNT=(\d+)/', $pattern['rrule'], $matches);
        $count = isset($matches[1]) ? (int) $matches[1] : 10;

        $this->assertRruleCompatibility(
            $pattern['rrule'],
            $start,
            $count,
            $pattern['description']
        );
    }

    /**
     * @dataProvider untilPatternProvider
     */
    public function testUntilTermination(array $pattern): void
    {
        $start = new DateTimeImmutable($pattern['start']);

        $this->assertRruleCompatibility(
            $pattern['rrule'],
            $start,
            20, // Use reasonable limit for until patterns
            $pattern['description']
        );
    }

    /**
     * Test different interval values for each frequency.
     */
    public function testIntervalVariations(): void
    {
        $patterns = [
            ['FREQ=DAILY;INTERVAL=1;COUNT=7', 'Daily every day', 7],
            ['FREQ=DAILY;INTERVAL=2;COUNT=5', 'Daily every 2 days', 5],
            ['FREQ=DAILY;INTERVAL=7;COUNT=4', 'Daily every 7 days', 4],
            ['FREQ=WEEKLY;INTERVAL=1;COUNT=4', 'Weekly every week', 4],
            ['FREQ=WEEKLY;INTERVAL=2;COUNT=3', 'Weekly every 2 weeks', 3],
            ['FREQ=MONTHLY;INTERVAL=1;COUNT=6', 'Monthly every month', 6],
            ['FREQ=MONTHLY;INTERVAL=3;COUNT=4', 'Monthly every 3 months', 4],
            ['FREQ=YEARLY;INTERVAL=1;COUNT=3', 'Yearly every year', 3],
            ['FREQ=YEARLY;INTERVAL=2;COUNT=2', 'Yearly every 2 years', 2],
        ];

        $start = new DateTimeImmutable('2025-01-15 14:30:00');

        foreach ($patterns as [$rrule, $description, $expectedCount]) {
            $this->assertRruleCompatibility($rrule, $start, $expectedCount, $description);
        }
    }

    /**
     * Test patterns across different starting dates and times.
     */
    public function testDifferentStartDates(): void
    {
        $startDates = [
            '2025-01-01 00:00:00', // Start of year, midnight
            '2025-06-15 12:00:00', // Mid-year, noon
            '2025-12-31 23:59:59', // End of year, almost midnight
            '2024-02-29 06:30:15', // Leap year Feb 29th
        ];

        $rrules = [
            'FREQ=DAILY;COUNT=5',
            'FREQ=WEEKLY;COUNT=4',
            'FREQ=MONTHLY;COUNT=3',
            'FREQ=YEARLY;COUNT=2',
        ];

        foreach ($startDates as $startDate) {
            foreach ($rrules as $rrule) {
                $start = new DateTimeImmutable($startDate);

                // Extract the count from the RRULE to use as limit
                preg_match('/COUNT=(\d+)/', $rrule, $matches);
                $count = isset($matches[1]) ? (int) $matches[1] : 10;

                $this->assertRruleCompatibility(
                    $rrule,
                    $start,
                    $count,
                    "Pattern {$rrule} starting {$startDate}"
                );
            }
        }
    }

    /**
     * Test leap year handling for different frequencies.
     */
    public function testLeapYearHandling(): void
    {
        // Start on Feb 29th, 2024 (leap year)
        $start = new DateTimeImmutable('2024-02-29 10:00:00');

        $patterns = [
            ['FREQ=DAILY;COUNT=5', 'Daily from leap day'],
            ['FREQ=WEEKLY;COUNT=8', 'Weekly from leap day'],
            ['FREQ=MONTHLY;COUNT=6', 'Monthly from leap day'],
            ['FREQ=YEARLY;COUNT=4', 'Yearly from leap day'],
        ];

        foreach ($patterns as [$rrule, $description]) {
            // Extract the count from the RRULE to use as limit
            preg_match('/COUNT=(\d+)/', $rrule, $matches);
            $count = isset($matches[1]) ? (int) $matches[1] : 10;

            $this->assertRruleCompatibility($rrule, $start, $count, $description);
        }
    }

    public static function basicFrequencyPatternProvider(): array
    {
        return array_map(
            fn (array $pattern) => [$pattern],
            RrulePatternGenerator::generateBasicFrequencyPatterns()
        );
    }

    public static function countPatternProvider(): array
    {
        return array_map(
            fn (array $pattern) => [$pattern],
            RrulePatternGenerator::generateCountPatterns()
        );
    }

    public static function untilPatternProvider(): array
    {
        return array_map(
            fn (array $pattern) => [$pattern],
            RrulePatternGenerator::generateUntilPatterns()
        );
    }
}
