<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Test week boundary calculations with different WKST values.
 *
 * This test class focuses specifically on cases where WKST affects
 * week boundary calculations, particularly with WEEKLY frequency
 * and INTERVAL > 1.
 */
final class DefaultOccurrenceGeneratorWeekBoundaryWkstTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    #[DataProvider('provideWeeklyIntervalBoundaryData')]
    public function testWeeklyIntervalWithWkstBoundaries(
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

    #[DataProvider('provideWeekStartBoundaryData')]
    public function testWeekStartBoundaryBehavior(
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

    public static function provideWeeklyIntervalBoundaryData(): array
    {
        return [
            // Test bi-weekly (INTERVAL=2) with different WKST values
            // This is where WKST should make a significant difference
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024 (Week 1)
                ['2024-01-01', '2024-01-15', '2024-01-29', '2024-02-12'],
                'Bi-weekly Monday with default WKST=MO',
            ],

            // Same pattern but with WKST=SU - should potentially differ
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;WKST=SU;COUNT=4',
                '2024-01-01', // Monday, Jan 1, 2024
                ['2024-01-01', '2024-01-15', '2024-01-29', '2024-02-12'],
                'Bi-weekly Monday with WKST=SU',
            ],

            // Test starting on a Sunday with different WKST
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;COUNT=4',
                '2023-12-31', // Sunday, Dec 31, 2023
                ['2023-12-31', '2024-01-14', '2024-01-28', '2024-02-11'],
                'Bi-weekly Sunday with default WKST=MO',
            ],

            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=SU;WKST=SU;COUNT=4',
                '2023-12-31', // Sunday, Dec 31, 2023
                ['2023-12-31', '2024-01-14', '2024-01-28', '2024-02-11'],
                'Bi-weekly Sunday with WKST=SU',
            ],

            // Test with WKST=TU (Tuesday start) and bi-weekly pattern
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU;WKST=TU;COUNT=4',
                '2024-01-02', // Tuesday, Jan 2, 2024
                ['2024-01-02', '2024-01-16', '2024-01-30', '2024-02-13'],
                'Bi-weekly Tuesday with WKST=TU',
            ],

            // Test tri-weekly (INTERVAL=3) pattern
            [
                'FREQ=WEEKLY;INTERVAL=3;BYDAY=WE;COUNT=4',
                '2024-01-03', // Wednesday, Jan 3, 2024
                ['2024-01-03', '2024-01-24', '2024-02-14', '2024-03-06'],
                'Tri-weekly Wednesday with default WKST=MO',
            ],

            [
                'FREQ=WEEKLY;INTERVAL=3;BYDAY=WE;WKST=SU;COUNT=4',
                '2024-01-03', // Wednesday, Jan 3, 2024
                ['2024-01-03', '2024-01-24', '2024-02-14', '2024-03-06'],
                'Tri-weekly Wednesday with WKST=SU',
            ],
        ];
    }

    public static function provideWeekStartBoundaryData(): array
    {
        return [
            // Test patterns that cross year boundaries with different WKST
            [
                'FREQ=WEEKLY;BYDAY=MO;COUNT=4',
                '2023-12-25', // Monday, Dec 25, 2023
                ['2023-12-25', '2024-01-01', '2024-01-08', '2024-01-15'],
                'Weekly Monday crossing year boundary with WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=MO;WKST=SU;COUNT=4',
                '2023-12-25', // Monday, Dec 25, 2023
                ['2023-12-25', '2024-01-01', '2024-01-08', '2024-01-15'],
                'Weekly Monday crossing year boundary with WKST=SU',
            ],

            // Test with Saturday/Sunday patterns around year boundary
            [
                'FREQ=WEEKLY;BYDAY=SA;COUNT=4',
                '2023-12-30', // Saturday, Dec 30, 2023
                ['2023-12-30', '2024-01-06', '2024-01-13', '2024-01-20'],
                'Weekly Saturday crossing year boundary with WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=SA;WKST=SU;COUNT=4',
                '2023-12-30', // Saturday, Dec 30, 2023
                ['2023-12-30', '2024-01-06', '2024-01-13', '2024-01-20'],
                'Weekly Saturday crossing year boundary with WKST=SU',
            ],

            // Test week boundaries in February (leap year aware)
            [
                'FREQ=WEEKLY;BYDAY=TH;COUNT=4',
                '2024-02-29', // Thursday, Feb 29, 2024 (leap year)
                ['2024-02-29', '2024-03-07', '2024-03-14', '2024-03-21'],
                'Weekly Thursday from leap day with WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=TH;WKST=SU;COUNT=4',
                '2024-02-29', // Thursday, Feb 29, 2024 (leap year)
                ['2024-02-29', '2024-03-07', '2024-03-14', '2024-03-21'],
                'Weekly Thursday from leap day with WKST=SU',
            ],

            // Edge case: Start on week boundary day
            [
                'FREQ=WEEKLY;BYDAY=MO;COUNT=3',
                '2024-01-01', // Monday, Jan 1, 2024 (start of week with WKST=MO)
                ['2024-01-01', '2024-01-08', '2024-01-15'],
                'Weekly Monday starting on Monday (week start) with WKST=MO',
            ],

            [
                'FREQ=WEEKLY;BYDAY=SU;WKST=SU;COUNT=3',
                '2024-01-07', // Sunday, Jan 7, 2024 (start of week with WKST=SU)
                ['2024-01-07', '2024-01-14', '2024-01-21'],
                'Weekly Sunday starting on Sunday (week start) with WKST=SU',
            ],
        ];
    }
}
