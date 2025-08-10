<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefaultOccurrenceGeneratorTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    public function testImplementsOccurrenceGeneratorInterface(): void
    {
        $this->assertInstanceOf(OccurrenceGenerator::class, $this->testOccurrenceGenerator);
    }

    #[DataProvider('provideDailyOccurrenceData')]
    public function testGenerateOccurrencesDaily(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $this->assertInstanceOf(Generator::class, $occurrences);

        $results = iterator_to_array($occurrences);
        $this->assertCount($expectedCount, $results);

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }
    }

    #[DataProvider('provideWeeklyOccurrenceData')]
    public function testGenerateOccurrencesWeekly(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $this->assertInstanceOf(Generator::class, $occurrences);

        $results = iterator_to_array($occurrences);
        $this->assertCount($expectedCount, $results);

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }
    }

    public function testGenerateOccurrencesWithLimit(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start, 3);

        $results = iterator_to_array($occurrences);
        $this->assertCount(3, $results);
        $this->assertEquals(new DateTimeImmutable('2025-01-01'), $results[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-02'), $results[1]);
        $this->assertEquals(new DateTimeImmutable('2025-01-03'), $results[2]);
    }

    public function testGenerateOccurrencesInRange(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY');
        $start = new DateTimeImmutable('2025-01-01');
        $rangeStart = new DateTimeImmutable('2025-01-03');
        $rangeEnd = new DateTimeImmutable('2025-01-05');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);

        $this->assertInstanceOf(Generator::class, $occurrences);

        $results = iterator_to_array($occurrences);
        $this->assertCount(3, $results);
        $this->assertEquals(new DateTimeImmutable('2025-01-03'), $results[0]);
        $this->assertEquals(new DateTimeImmutable('2025-01-04'), $results[1]);
        $this->assertEquals(new DateTimeImmutable('2025-01-05'), $results[2]);
    }

    public function testGenerateOccurrencesWithCountZero(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;COUNT=0');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $results = iterator_to_array($occurrences);
        $this->assertCount(0, $results);
    }

    public function testGenerateOccurrencesWithUntilBeforeStart(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $results = iterator_to_array($occurrences);
        $this->assertCount(0, $results);
    }

    public static function provideDailyOccurrenceData(): array
    {
        return [
            'daily with count 3' => [
                'FREQ=DAILY;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-01', '2025-01-02', '2025-01-03'],
            ],
            'daily every 2 days with count 3' => [
                'FREQ=DAILY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-01', '2025-01-03', '2025-01-05'],
            ],
            'daily until specific date' => [
                'FREQ=DAILY;UNTIL=20250103T235959Z',
                '2025-01-01',
                3,
                ['2025-01-01', '2025-01-02', '2025-01-03'],
            ],
        ];
    }

    #[DataProvider('provideByMonthDayOccurrenceData')]
    public function testGenerateOccurrencesWithByMonthDay(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $this->assertInstanceOf(Generator::class, $occurrences);

        $results = iterator_to_array($occurrences);
        $this->assertCount($expectedCount, $results);

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }
    }

    public static function provideWeeklyOccurrenceData(): array
    {
        return [
            'weekly with count 3' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-08', '2025-01-15'],
            ],
            'weekly every 2 weeks with count 3' => [
                'FREQ=WEEKLY;INTERVAL=2;COUNT=3',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-15', '2025-01-29'],
            ],
            'weekly until specific date' => [
                'FREQ=WEEKLY;UNTIL=20250115T235959Z',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-08', '2025-01-15'],
            ],
        ];
    }

    public static function provideByMonthDayOccurrenceData(): array
    {
        return [
            // Monthly with single positive BYMONTHDAY
            'monthly on 15th, count 3' => [
                'FREQ=MONTHLY;BYMONTHDAY=15;COUNT=3',
                '2025-01-10', // Start before the 15th
                3,
                ['2025-01-15', '2025-02-15', '2025-03-15'],
            ],

            // Monthly with single negative BYMONTHDAY
            'monthly on last day, count 3' => [
                'FREQ=MONTHLY;BYMONTHDAY=-1;COUNT=3',
                '2025-01-10',
                3,
                ['2025-01-31', '2025-02-28', '2025-03-31'], // Different month lengths
            ],

            // Monthly with multiple BYMONTHDAY values
            'monthly on 1st and 15th, count 4' => [
                'FREQ=MONTHLY;BYMONTHDAY=1,15;COUNT=4',
                '2025-01-10', // Start between 1st and 15th
                4,
                ['2025-01-15', '2025-02-01', '2025-02-15', '2025-03-01'],
            ],

            // Monthly with mixed positive and negative BYMONTHDAY
            'monthly on 1st and last day, count 4' => [
                'FREQ=MONTHLY;BYMONTHDAY=1,-1;COUNT=4',
                '2025-01-10',
                4,
                ['2025-01-31', '2025-02-01', '2025-02-28', '2025-03-01'],
            ],

            // Yearly with BYMONTHDAY (should apply to all months)
            'yearly on 15th, count 3' => [
                'FREQ=YEARLY;BYMONTHDAY=15;COUNT=3',
                '2025-01-10',
                3,
                ['2025-01-15', '2026-01-15', '2027-01-15'],
            ],

            // Edge case: February 29th in non-leap year (should be skipped)
            'monthly on 29th, crossing non-leap February' => [
                'FREQ=MONTHLY;BYMONTHDAY=29;COUNT=3',
                '2025-01-10',
                3,
                ['2025-01-29', '2025-03-29', '2025-04-29'], // Feb skipped (only 28 days)
            ],

            // Edge case: February 29th in leap year
            'monthly on 29th, crossing leap February' => [
                'FREQ=MONTHLY;BYMONTHDAY=29;COUNT=3',
                '2024-01-10',
                3,
                ['2024-01-29', '2024-02-29', '2024-03-29'], // Feb included (29 days)
            ],

            // Edge case: April 31st (should be skipped)
            'monthly on 31st, crossing 30-day months' => [
                'FREQ=MONTHLY;BYMONTHDAY=31;COUNT=4',
                '2025-01-10',
                4,
                ['2025-01-31', '2025-03-31', '2025-05-31', '2025-07-31'], // April/June skipped
            ],

            // BYMONTHDAY starting exactly on a matching day
            'monthly on 15th, starting on 15th' => [
                'FREQ=MONTHLY;BYMONTHDAY=15;COUNT=3',
                '2025-01-15',
                3,
                ['2025-01-15', '2025-02-15', '2025-03-15'],
            ],

            // BYMONTHDAY with INTERVAL
            'monthly on 15th, every 2 months' => [
                'FREQ=MONTHLY;INTERVAL=2;BYMONTHDAY=15;COUNT=3',
                '2025-01-10',
                3,
                ['2025-01-15', '2025-03-15', '2025-05-15'],
            ],
        ];
    }

    #[DataProvider('provideByWeekNoOccurrenceData')]
    public function testGenerateOccurrencesWithByWeekNo(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $this->assertInstanceOf(Generator::class, $occurrences);

        $results = iterator_to_array($occurrences);
        $this->assertCount($expectedCount, $results);

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }
    }

    #[DataProvider('provideBySetPosOccurrenceData')]
    public function testGenerateOccurrencesWithBySetPos(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->testOccurrenceGenerator->generateOccurrences($rrule, $start);

        $this->assertInstanceOf(Generator::class, $occurrences);

        // Convert to array with safety limit to prevent infinite loops during testing
        $results = [];
        $count = 0;
        $maxIterations = max($expectedCount + 10, 100); // Safety limit

        foreach ($occurrences as $occurrence) {
            $results[] = $occurrence;
            ++$count;
            if ($count >= $maxIterations) {
                break; // Safety limit reached
            }
        }

        $this->assertCount($expectedCount, $results);

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(new DateTimeImmutable($expectedDate), $results[$index]);
        }
    }

    public static function provideByWeekNoOccurrenceData(): array
    {
        return [
            // Yearly with single week number (returns all days of specified week)
            'yearly week 13, count 3' => [
                'FREQ=YEARLY;BYWEEKNO=13;COUNT=3',
                '2025-01-01', // Wednesday - start at beginning of year
                3,
                ['2025-03-24', '2025-03-25', '2025-03-26'], // All days of week 13, 2025
            ],

            // Yearly with multiple week numbers (returns days from first specified week)
            'yearly quarterly weeks, count 4' => [
                'FREQ=YEARLY;BYWEEKNO=13,26,39,52;COUNT=4',
                '2025-01-01', // Wednesday
                4,
                ['2025-03-24', '2025-03-25', '2025-03-26', '2025-03-27'], // First 4 days of week 13, 2025
            ],

            // Yearly with bi-annual pattern (start on same day of week)
            'yearly bi-annual weeks, count 4' => [
                'FREQ=YEARLY;BYWEEKNO=1,26;COUNT=4',
                '2025-01-01', // Wednesday
                4,
                ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04'], // First 4 days of week 1, 2025
            ],

            // Week 53 testing (leap week) - 2026 has week 53
            'yearly week 53, count 2' => [
                'FREQ=YEARLY;BYWEEKNO=53;COUNT=2',
                '2025-01-01', // Start in year without week 53 (Wednesday)
                2,
                ['2026-12-28', '2026-12-29'], // First 2 days of week 53, 2026
            ],

            // Mixed weeks including week 53 - starting in year with week 53
            'yearly with week 53 mixed, count 3' => [
                'FREQ=YEARLY;BYWEEKNO=52,53;COUNT=3',
                '2026-01-01', // Thursday, start in year with week 53
                3,
                ['2026-12-21', '2026-12-22', '2026-12-23'], // First 3 days of week 52, 2026
            ],

            // Starting in middle of year
            'yearly week 26, starting mid-year' => [
                'FREQ=YEARLY;BYWEEKNO=26;COUNT=3',
                '2025-07-01', // Tuesday, start after week 26
                3,
                ['2026-06-22', '2026-06-23', '2026-06-24'], // First 3 days of week 26, 2026
            ],

            // Starting exactly on week match
            'yearly week 13, starting on week 13' => [
                'FREQ=YEARLY;BYWEEKNO=13;COUNT=3',
                '2025-03-24', // Monday of week 13, 2025
                3,
                ['2025-03-24', '2025-03-25', '2025-03-26'], // First 3 days of week 13, 2025
            ],

            // BYWEEKNO with INTERVAL
            'yearly week 26, every 2 years' => [
                'FREQ=YEARLY;INTERVAL=2;BYWEEKNO=26;COUNT=3',
                '2025-01-01', // Wednesday
                3,
                ['2025-06-23', '2025-06-24', '2025-06-25'], // First 3 days of week 26, 2025
            ],

            // Multiple weeks with interval
            'yearly weeks 1,26, every 2 years' => [
                'FREQ=YEARLY;INTERVAL=2;BYWEEKNO=1,26;COUNT=4',
                '2025-01-01', // Wednesday
                4,
                ['2025-01-01', '2025-01-02', '2025-01-03', '2025-01-04'], // First 4 days of week 1, 2025
            ],

            // Edge case: Week 1 across year boundary - starting late in year
            'yearly week 1, count 3' => [
                'FREQ=YEARLY;BYWEEKNO=1;COUNT=3',
                '2025-12-01', // Monday, late in year (week 1 already passed)
                3,
                ['2025-12-29', '2025-12-30', '2025-12-31'], // First 3 days of week 1, 2025 (Dec 29-31)
            ],
        ];
    }

    public static function provideBySetPosOccurrenceData(): array
    {
        return [
            // First Monday of each month - simple case
            'first Monday monthly, count 3' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-06', '2025-02-03', '2025-03-03'], // First Monday each month
            ],

            // Last Friday of each month - simple case
            'last Friday monthly, count 3' => [
                'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-31', '2025-02-28', '2025-03-28'], // Last Friday each month
            ],

            // Real-world pattern: Last Sunday of March (daylight saving time transition)
            'last Sunday of March yearly, count 3' => [
                'FREQ=YEARLY;BYMONTH=3;BYDAY=SU;BYSETPOS=-1;COUNT=3',
                '2025-01-01',
                3,
                ['2025-03-30', '2026-03-29', '2027-03-28'], // Last Sunday of March each year
            ],

            // First and last workday of each month
            'first and last weekday monthly, count 4' => [
                'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;COUNT=4',
                '2025-01-01', // Wednesday
                4,
                ['2025-01-01', '2025-01-31', '2025-02-03', '2025-02-28'], // First and last weekday alternating
            ],

            // Second and third Tuesday of each month (complex selection)
            'second and third Tuesday monthly, count 4' => [
                'FREQ=MONTHLY;BYDAY=TU;BYSETPOS=2,3;COUNT=4',
                '2025-01-01',
                4,
                ['2025-01-14', '2025-01-21', '2025-02-11', '2025-02-18'], // 2nd and 3rd Tuesday alternating
            ],

            // 15th or last day of month (BYMONTHDAY with BYSETPOS)
            '15th or last day monthly, count 3' => [
                'FREQ=MONTHLY;BYMONTHDAY=15,-1;BYSETPOS=-1;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-31', '2025-02-28', '2025-03-31'], // Always the last day (since -1 comes after 15th position)
            ],

            // First three weekdays of each month
            'first three weekdays monthly, count 6' => [
                'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,3;COUNT=6',
                '2025-01-01', // Wednesday
                6,
                ['2025-01-01', '2025-01-02', '2025-01-03', '2025-02-03', '2025-02-04', '2025-02-05'], // First 3 weekdays Jan and Feb
            ],

            // Last two weekdays of each month
            'last two weekdays monthly, count 4' => [
                'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-2,-1;COUNT=4',
                '2025-01-01',
                4,
                ['2025-01-30', '2025-01-31', '2025-02-27', '2025-02-28'], // Last 2 weekdays Jan and Feb
            ],

            // 2nd Sunday of every other month (bi-monthly with interval)
            '2nd Sunday bi-monthly, count 3' => [
                'FREQ=MONTHLY;INTERVAL=2;BYDAY=SU;BYSETPOS=2;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-12', '2025-03-09', '2025-05-11'], // 2nd Sunday every 2 months
            ],

            // Quarterly: last Thursday of March, June, September, December
            'last Thursday quarterly, count 4' => [
                'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=TH;BYSETPOS=-1;COUNT=4',
                '2025-01-01',
                4,
                ['2025-03-27', '2025-06-26', '2025-09-25', '2025-12-25'], // Last Thursday quarterly
            ],

            // Weekly pattern with BYSETPOS (every Tuesday and Thursday, take first one)
            'weekly TU,TH take first, count 4' => [
                'FREQ=WEEKLY;BYDAY=TU,TH;BYSETPOS=1;COUNT=4',
                '2025-01-01', // Wednesday
                4,
                ['2025-01-02', '2025-01-07', '2025-01-14', '2025-01-21'], // Tuesday only (first of TU,TH each week)
            ],

            // BYSETPOS with BYWEEKNO - first day of specific weeks
            'first day of weeks 13,26, count 4' => [
                'FREQ=YEARLY;BYWEEKNO=13,26;BYSETPOS=1;COUNT=4',
                '2025-01-01',
                4,
                ['2025-03-24', '2025-06-23', '2026-03-23', '2026-06-22'], // Monday (first day) of weeks 13,26 each year
            ],

            // Complex multi-position selection
            'first, second, second-to-last, and last workday monthly, count 8' => [
                'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,2,-2,-1;COUNT=8',
                '2025-01-01', // Wednesday
                8,
                ['2025-01-01', '2025-01-02', '2025-01-30', '2025-01-31', '2025-02-03', '2025-02-04', '2025-02-27', '2025-02-28'], // First 2 and last 2 weekdays for Jan and Feb
            ],
        ];
    }
}
