<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefaultOccurrenceGeneratorTest extends TestCase
{
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
        $rrule = Rrule::fromString($rruleString);
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
        $rrule = Rrule::fromString($rruleString);
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
        $rrule = Rrule::fromString('FREQ=DAILY');
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
        $rrule = Rrule::fromString('FREQ=DAILY');
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
        $rrule = Rrule::fromString('FREQ=DAILY;COUNT=0');
        $start = new DateTimeImmutable('2025-01-01');

        $occurrences = $this->generator->generateOccurrences($rrule, $start);

        $results = iterator_to_array($occurrences);
        $this->assertCount(0, $results);
    }

    public function testGenerateOccurrencesWithUntilBeforeStart(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY;UNTIL=20241231T235959Z');
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
                ['2025-01-01', '2025-01-02', '2025-01-03']
            ],
            'daily every 2 days with count 3' => [
                'FREQ=DAILY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                3,
                ['2025-01-01', '2025-01-03', '2025-01-05']
            ],
            'daily until specific date' => [
                'FREQ=DAILY;UNTIL=20250103T235959Z',
                '2025-01-01',
                3,
                ['2025-01-01', '2025-01-02', '2025-01-03']
            ],
        ];
    }

    public static function provideWeeklyOccurrenceData(): array
    {
        return [
            'weekly with count 3' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-08', '2025-01-15']
            ],
            'weekly every 2 weeks with count 3' => [
                'FREQ=WEEKLY;INTERVAL=2;COUNT=3',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-15', '2025-01-29']
            ],
            'weekly until specific date' => [
                'FREQ=WEEKLY;UNTIL=20250115T235959Z',
                '2025-01-01', // Wednesday
                3,
                ['2025-01-01', '2025-01-08', '2025-01-15']
            ],
        ];
    }
}