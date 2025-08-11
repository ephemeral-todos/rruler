<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test BYDAY patterns with different WKST (Week Start) configurations.
 *
 * This test class verifies that BYDAY patterns respect the WKST parameter
 * for determining week boundaries. According to RFC 5545, WKST defines
 * which day is considered the first day of the week for calculation purposes.
 *
 * Key scenarios tested:
 * - Weekly BYDAY patterns with different WKST values
 * - Week boundary calculations for different start days
 * - Multiple weekdays in BYDAY with WKST variations
 * - Positional BYDAY patterns (1MO, -1FR) with WKST
 * - Edge cases around month/year boundaries
 */
final class DefaultOccurrenceGeneratorByDayWkstTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    #[DataProvider('provideWeeklyByDayWithWkstData')]
    public function testWeeklyByDayWithWkst(
        string $rruleString,
        string $startDate,
        array $expectedDates,
        string $description,
    ): void {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = [];
        $limit = count($expectedDates);
        foreach ($this->generator->generateOccurrences($rrule, $start, $limit) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        $this->assertEquals($expectedDates, $occurrences, $description);
    }

    #[DataProvider('provideWeeklyByDayMultipleWkstData')]
    public function testWeeklyByDayMultipleWeekdaysWithWkst(
        string $rruleString,
        string $startDate,
        array $expectedDates,
        string $description,
    ): void {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = [];
        $limit = count($expectedDates);
        foreach ($this->generator->generateOccurrences($rrule, $start, $limit) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        $this->assertEquals($expectedDates, $occurrences, $description);
    }

    #[DataProvider('providePositionalByDayWithWkstData')]
    public function testPositionalByDayWithWkst(
        string $rruleString,
        string $startDate,
        array $expectedDates,
        string $description,
    ): void {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = [];
        $limit = count($expectedDates);
        foreach ($this->generator->generateOccurrences($rrule, $start, $limit) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        $this->assertEquals($expectedDates, $occurrences, $description);
    }

    public static function provideWeeklyByDayWithWkstData(): array
    {
        return [
            // Test basic weekly BYDAY with default WKST=MO
            [
                'FREQ=WEEKLY;BYDAY=WE;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-03', '2024-01-10', '2024-01-17', '2024-01-24'],
                'Weekly Wednesday with default WKST=MO',
            ],

            // Test same pattern with WKST=SU
            [
                'FREQ=WEEKLY;BYDAY=WE;WKST=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-03', '2024-01-10', '2024-01-17', '2024-01-24'],
                'Weekly Wednesday with WKST=SU (should produce same result)',
            ],

            // Test weekly BYDAY where WKST affects week boundaries
            [
                'FREQ=WEEKLY;BYDAY=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-07', '2024-01-14', '2024-01-21', '2024-01-28'],
                'Weekly Sunday with default WKST=MO',
            ],

            // Test weekly BYDAY=SU with WKST=SU
            [
                'FREQ=WEEKLY;BYDAY=SU;WKST=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-07', '2024-01-14', '2024-01-21', '2024-01-28'],
                'Weekly Sunday with WKST=SU',
            ],

            // Test weekly BYDAY=MO with different WKST values
            [
                'FREQ=WEEKLY;BYDAY=MO;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-08', '2024-01-15', '2024-01-22'],
                'Weekly Monday with default WKST=MO, starting on Monday',
            ],

            [
                'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-08', '2024-01-15', '2024-01-22'],
                'Weekly Monday with WKST=SU, starting on Monday',
            ],

            // Test with WKST=TU (Tuesday as week start)
            [
                'FREQ=WEEKLY;BYDAY=TU;WKST=TU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-02', '2024-01-09', '2024-01-16', '2024-01-23'],
                'Weekly Tuesday with WKST=TU',
            ],

            // Test edge case: start date after the target weekday in week
            [
                'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
                '2024-01-03', // Wednesday, Jan 3, 2024
                ['2024-01-08', '2024-01-15', '2024-01-22', '2024-01-29'],
                'Weekly Monday starting Wednesday, with WKST=SU',
            ],
        ];
    }

    public static function provideWeeklyByDayMultipleWkstData(): array
    {
        return [
            // Test multiple weekdays with default WKST=MO
            [
                'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-03', '2024-01-05', '2024-01-08', '2024-01-10', '2024-01-12'],
                'Weekly MWF with default WKST=MO',
            ],

            // Test same pattern with WKST=SU
            [
                'FREQ=WEEKLY;BYDAY=MO,WE,FR;WKST=SU;COUNT=6',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-03', '2024-01-05', '2024-01-08', '2024-01-10', '2024-01-12'],
                'Weekly MWF with WKST=SU',
            ],

            // Test weekend days with different WKST
            [
                'FREQ=WEEKLY;BYDAY=SA,SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-06', '2024-01-07', '2024-01-13', '2024-01-14'],
                'Weekly weekend with default WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=SA,SU;WKST=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-06', '2024-01-07', '2024-01-13', '2024-01-14'],
                'Weekly weekend with WKST=SU',
            ],

            // Test all weekdays with WKST variations
            [
                'FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR,SA,SU;COUNT=7',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05', '2024-01-06', '2024-01-07'],
                'Weekly all days with default WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=SU,MO,TU,WE,TH,FR,SA;WKST=SU;COUNT=7',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-02', '2024-01-03', '2024-01-04', '2024-01-05', '2024-01-06', '2024-01-07'],
                'Weekly all days with WKST=SU',
            ],
        ];
    }

    public static function providePositionalByDayWithWkstData(): array
    {
        return [
            // Test first Monday of month with different WKST values
            [
                'FREQ=MONTHLY;BYDAY=1MO;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-02-05', '2024-03-04'],
                'First Monday of month with default WKST=MO',
            ],

            [
                'FREQ=MONTHLY;BYDAY=1MO;WKST=SU;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-02-05', '2024-03-04'],
                'First Monday of month with WKST=SU',
            ],

            // Test last Friday of month
            [
                'FREQ=MONTHLY;BYDAY=-1FR;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-26', '2024-02-23', '2024-03-29'],
                'Last Friday of month with default WKST=MO',
            ],

            [
                'FREQ=MONTHLY;BYDAY=-1FR;WKST=SU;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-26', '2024-02-23', '2024-03-29'],
                'Last Friday of month with WKST=SU',
            ],

            // Test second Tuesday of month with WKST=TU
            [
                'FREQ=MONTHLY;BYDAY=2TU;WKST=TU;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-09', '2024-02-13', '2024-03-12'],
                'Second Tuesday of month with WKST=TU',
            ],

            // Test yearly positional patterns
            [
                'FREQ=YEARLY;BYDAY=1MO;BYMONTH=9;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-09-02', '2025-09-01', '2026-09-07'],
                'First Monday of September yearly with default WKST=MO',
            ],

            [
                'FREQ=YEARLY;BYDAY=1MO;BYMONTH=9;WKST=SU;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-09-02', '2025-09-01', '2026-09-07'],
                'First Monday of September yearly with WKST=SU',
            ],
        ];
    }
}
