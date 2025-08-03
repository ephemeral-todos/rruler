<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefaultOccurrenceGeneratorTest extends TestCase
{
    use TestRrulerBehavior;
    private OccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testImplementsOccurrenceGeneratorInterface(): void
    {
        $this->assertInstanceOf(OccurrenceGenerator::class, $this->generator);
    }

    #[DataProvider('provideDailyOccurrenceData')]
    public function testGenerateOccurrencesDaily(string $rruleString, string $startDate, int $expectedCount, array $expectedDates): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

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

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

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

        $occurrences = $this->generator->generateOccurrences($rrule, $start, 3);

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

        $occurrences = $this->generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);

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

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

        $results = iterator_to_array($occurrences);
        $this->assertCount(0, $results);
    }

    public function testGenerateOccurrencesWithUntilBeforeStart(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

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

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

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
}
