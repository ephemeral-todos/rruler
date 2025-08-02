<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByDayOccurrenceTest extends TestCase
{
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    #[DataProvider('provideWeeklyByDayScenarios')]
    public function testWeeklyByDayOccurrences(string $rruleString, string $startDate, array $expectedDates): void
    {
        $rrule = $this->rruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = [];
        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start, 10) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
            ++$count;
            if ($count >= count($expectedDates)) {
                break;
            }
        }

        $this->assertEquals($expectedDates, $occurrences);
    }

    #[DataProvider('provideMonthlyByDayScenarios')]
    public function testMonthlyByDayOccurrences(string $rruleString, string $startDate, array $expectedDates): void
    {
        $rrule = $this->rruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);

        $occurrences = [];
        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start, 6) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
            ++$count;
            if ($count >= count($expectedDates)) {
                break;
            }
        }

        $this->assertEquals($expectedDates, $occurrences);
    }

    public static function provideWeeklyByDayScenarios(): array
    {
        return [
            // Every Monday
            [
                'FREQ=WEEKLY;BYDAY=MO',
                '2024-01-01', // Monday
                ['2024-01-01', '2024-01-08', '2024-01-15', '2024-01-22', '2024-01-29'],
            ],

            // Every Monday, Wednesday, Friday
            [
                'FREQ=WEEKLY;BYDAY=MO,WE,FR',
                '2024-01-01', // Monday
                ['2024-01-01', '2024-01-03', '2024-01-05', '2024-01-08', '2024-01-10'],
            ],

            // Every Tuesday and Thursday, starting on a Friday
            [
                'FREQ=WEEKLY;BYDAY=TU,TH',
                '2024-01-05', // Friday
                ['2024-01-09', '2024-01-11', '2024-01-16', '2024-01-18', '2024-01-23'], // Next Tuesday onwards
            ],

            // Weekend days only
            [
                'FREQ=WEEKLY;BYDAY=SA,SU',
                '2024-01-06', // Saturday
                ['2024-01-06', '2024-01-07', '2024-01-13', '2024-01-14', '2024-01-20'],
            ],

            // Every 2 weeks on Monday
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO',
                '2024-01-01', // Monday
                ['2024-01-01', '2024-01-15', '2024-01-29', '2024-02-12', '2024-02-26'],
            ],
        ];
    }

    public static function provideMonthlyByDayScenarios(): array
    {
        return [
            // First Monday of each month
            [
                'FREQ=MONTHLY;BYDAY=1MO',
                '2024-01-01', // First Monday
                ['2024-01-01', '2024-02-05', '2024-03-04', '2024-04-01', '2024-05-06'],
            ],

            // Last Friday of each month
            [
                'FREQ=MONTHLY;BYDAY=-1FR',
                '2024-01-26', // Last Friday of January
                ['2024-01-26', '2024-02-23', '2024-03-29', '2024-04-26', '2024-05-31'],
            ],

            // Second Tuesday of each month
            [
                'FREQ=MONTHLY;BYDAY=2TU',
                '2024-01-09', // Second Tuesday
                ['2024-01-09', '2024-02-13', '2024-03-12', '2024-04-09', '2024-05-14'],
            ],

            // Every Monday in the month (no position)
            [
                'FREQ=MONTHLY;BYDAY=MO',
                '2024-01-01', // First Monday
                ['2024-01-01', '2024-01-08', '2024-01-15', '2024-01-22', '2024-01-29'], // All Mondays in January
            ],
        ];
    }

    public function testDailyByDayFiltering(): void
    {
        // DAILY but only on weekdays (MO-FR)
        $rrule = $this->rruler->parse('FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR');
        $start = new DateTimeImmutable('2024-01-01'); // Monday

        $occurrences = [];
        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start, 10) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
            ++$count;
            if ($count >= 7) { // First week
                break;
            }
        }

        $expected = [
            '2024-01-01', // Mon
            '2024-01-02', // Tue
            '2024-01-03', // Wed
            '2024-01-04', // Thu
            '2024-01-05', // Fri
            '2024-01-08', // Mon (skips weekend)
            '2024-01-09', // Tue
        ];

        $this->assertEquals($expected, $occurrences);
    }

    public function testByDayWithCount(): void
    {
        $rrule = $this->rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=5');
        $start = new DateTimeImmutable('2024-01-01'); // Monday

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        $expected = [
            '2024-01-01', // Mon
            '2024-01-03', // Wed
            '2024-01-05', // Fri
            '2024-01-08', // Mon
            '2024-01-10', // Wed
        ];

        $this->assertEquals($expected, $occurrences);
        $this->assertCount(5, $occurrences);
    }

    public function testByDayWithUntil(): void
    {
        $rrule = $this->rruler->parse('FREQ=WEEKLY;BYDAY=MO;UNTIL=20240115T000000Z');
        $start = new DateTimeImmutable('2024-01-01'); // Monday

        $occurrences = [];
        foreach ($this->generator->generateOccurrences($rrule, $start) as $occurrence) {
            $occurrences[] = $occurrence->format('Y-m-d');
        }

        $expected = [
            '2024-01-01',
            '2024-01-08',
            '2024-01-15', // Until date
        ];

        $this->assertEquals($expected, $occurrences);
    }
}
