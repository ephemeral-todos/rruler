<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\DateValidationUtils;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateValidationUtilsTest extends TestCase
{
    #[DataProvider('provideDaysInMonthData')]
    public function testDaysInMonth(int $expected, int $year, int $month): void
    {
        $result = DateValidationUtils::getDaysInMonth($year, $month);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideLeapYearData')]
    public function testIsLeapYear(bool $expected, int $year): void
    {
        $result = DateValidationUtils::isLeapYear($year);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideNegativeDayResolutionData')]
    public function testResolveNegativeDayToPositive(int $expected, int $negativeDay, int $year, int $month): void
    {
        $result = DateValidationUtils::resolveNegativeDayToPositive($negativeDay, $year, $month);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideValidDateData')]
    public function testIsValidDate(bool $expected, int $year, int $month, int $day): void
    {
        $result = DateValidationUtils::isValidDate($year, $month, $day);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideByMonthDayValidationData')]
    public function testValidateByMonthDayForDate(bool $expected, array $byMonthDay, DateTimeImmutable $date): void
    {
        $result = DateValidationUtils::dateMatchesByMonthDay($date, $byMonthDay);

        $this->assertEquals($expected, $result);
    }

    public static function provideDaysInMonthData(): array
    {
        return [
            // Regular months in non-leap year
            [31, 2023, 1],  // January
            [28, 2023, 2],  // February (non-leap)
            [31, 2023, 3],  // March
            [30, 2023, 4],  // April
            [31, 2023, 5],  // May
            [30, 2023, 6],  // June
            [31, 2023, 7],  // July
            [31, 2023, 8],  // August
            [30, 2023, 9],  // September
            [31, 2023, 10], // October
            [30, 2023, 11], // November
            [31, 2023, 12], // December

            // February in leap years
            [29, 2024, 2],  // February (leap year)
            [29, 2000, 2],  // February (leap year divisible by 400)
            [28, 1900, 2],  // February (non-leap year divisible by 100 but not 400)
            [29, 2004, 2],  // February (leap year divisible by 4)
        ];
    }

    public static function provideLeapYearData(): array
    {
        return [
            [true, 2024],   // Divisible by 4
            [false, 2023],  // Not divisible by 4
            [true, 2000],   // Divisible by 400
            [false, 1900],  // Divisible by 100 but not 400
            [true, 2004],   // Divisible by 4
            [false, 1999],  // Not divisible by 4
            [true, 1996],   // Divisible by 4
            [false, 2001],  // Not divisible by 4
        ];
    }

    public static function provideNegativeDayResolutionData(): array
    {
        return [
            // January (31 days)
            [31, -1, 2023, 1],  // Last day
            [30, -2, 2023, 1],  // Second to last
            [1, -31, 2023, 1],  // First day

            // February non-leap (28 days)
            [28, -1, 2023, 2],  // Last day
            [27, -2, 2023, 2],  // Second to last
            [1, -28, 2023, 2],  // First day

            // February leap year (29 days)
            [29, -1, 2024, 2],  // Last day
            [28, -2, 2024, 2],  // Second to last
            [1, -29, 2024, 2],  // First day

            // April (30 days)
            [30, -1, 2023, 4],  // Last day
            [29, -2, 2023, 4],  // Second to last
            [1, -30, 2023, 4],  // First day
        ];
    }

    public static function provideValidDateData(): array
    {
        return [
            // Valid dates
            [true, 2023, 1, 1],   // January 1st
            [true, 2023, 1, 31],  // January 31st
            [true, 2023, 2, 28],  // February 28th (non-leap)
            [true, 2024, 2, 29],  // February 29th (leap year)
            [true, 2023, 4, 30],  // April 30th
            [true, 2023, 12, 31], // December 31st

            // Invalid dates
            [false, 2023, 2, 29], // February 29th (non-leap)
            [false, 2023, 4, 31], // April 31st
            [false, 2023, 6, 31], // June 31st
            [false, 2023, 9, 31], // September 31st
            [false, 2023, 11, 31], // November 31st
            [false, 2023, 13, 1], // Invalid month
            [false, 2023, 0, 1],  // Invalid month
            [false, 2023, 1, 0],  // Invalid day
            [false, 2023, 1, 32], // Invalid day
        ];
    }

    public static function provideByMonthDayValidationData(): array
    {
        return [
            // Single positive day matches
            [true, [15], new DateTimeImmutable('2023-01-15')],
            [false, [15], new DateTimeImmutable('2023-01-16')],

            // Single negative day matches
            [true, [-1], new DateTimeImmutable('2023-01-31')],  // Last day of January
            [true, [-1], new DateTimeImmutable('2023-02-28')],  // Last day of February (non-leap)
            [true, [-1], new DateTimeImmutable('2024-02-29')],  // Last day of February (leap)
            [false, [-1], new DateTimeImmutable('2023-01-30')], // Not last day

            // Multiple values
            [true, [1, 15, -1], new DateTimeImmutable('2023-01-01')],
            [true, [1, 15, -1], new DateTimeImmutable('2023-01-15')],
            [true, [1, 15, -1], new DateTimeImmutable('2023-01-31')],
            [false, [1, 15, -1], new DateTimeImmutable('2023-01-16')],

            // Edge cases with negative values
            [true, [-2], new DateTimeImmutable('2023-01-30')],  // Second to last in January
            [true, [-2], new DateTimeImmutable('2023-02-27')],  // Second to last in February (non-leap)
            [true, [-2], new DateTimeImmutable('2024-02-28')],  // Second to last in February (leap)
            [false, [-2], new DateTimeImmutable('2023-01-29')], // Not second to last

            // Mixed positive and negative
            [true, [15, -15], new DateTimeImmutable('2023-01-15')],
            [true, [15, -15], new DateTimeImmutable('2023-01-17')], // 17th = -15 in January (31-15+1=17)
            [false, [15, -15], new DateTimeImmutable('2023-01-16')],
        ];
    }

    #[DataProvider('provideIsoWeekNumberData')]
    public function testGetIsoWeekNumber(int $expected, string $dateString): void
    {
        $date = new DateTimeImmutable($dateString);
        $result = DateValidationUtils::getIsoWeekNumber($date);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideFirstDateOfWeekData')]
    public function testGetFirstDateOfWeek(string $expectedDateString, int $year, int $weekNumber): void
    {
        $result = DateValidationUtils::getFirstDateOfWeek($year, $weekNumber);
        $expected = new DateTimeImmutable($expectedDateString);

        $this->assertEquals($expected->format('Y-m-d'), $result->format('Y-m-d'));
    }

    #[DataProvider('provideYearHasWeek53Data')]
    public function testYearHasWeek53(bool $expected, int $year): void
    {
        $result = DateValidationUtils::yearHasWeek53($year);

        $this->assertEquals($expected, $result);
    }

    public static function provideIsoWeekNumberData(): array
    {
        return [
            // Week 1 scenarios - week containing January 4th
            [1, '2023-01-02'], // Monday of week 1
            [1, '2023-01-03'], // Tuesday of week 1
            [1, '2023-01-04'], // Wednesday of week 1 (Jan 4th)
            [1, '2023-01-05'], // Thursday of week 1
            [1, '2023-01-08'], // Sunday of week 1

            // Week 2
            [2, '2023-01-09'], // Monday of week 2
            [2, '2023-01-15'], // Sunday of week 2

            // Mid-year weeks
            [26, '2023-06-26'], // Monday of week 26
            [26, '2023-07-02'], // Sunday of week 26
            [52, '2023-12-25'], // Monday of week 52
            [52, '2023-12-31'], // Sunday of week 52 (last day of 2023)

            // Year boundary edge cases
            [52, '2022-12-26'], // Monday of week 52, 2022
            [52, '2022-12-27'], // Tuesday of week 52, 2022
            [52, '2023-01-01'], // Sunday of week 52, 2022 (belongs to 2022!)

            // Leap week scenarios (years with 53 weeks)
            [53, '2020-12-28'], // Monday of week 53 in 2020
            [53, '2020-12-29'], // Tuesday of week 53 in 2020
            [53, '2020-12-30'], // Wednesday of week 53 in 2020
            [53, '2020-12-31'], // Thursday of week 53 in 2020
            [53, '2021-01-01'], // Friday belongs to week 53 of 2020
            [53, '2021-01-03'], // Sunday of week 53 in 2020

            // More edge cases
            [1, '2024-01-01'], // Monday, Jan 1st is week 1
            [2, '2025-01-06'], // Monday of week 2 (week 1 started Dec 30, 2024)
            [2, '2026-01-05'], // Monday of week 2 (week 1 started Dec 29, 2025)

            // Historical known values
            [1, '2018-01-01'], // Monday, Jan 1st 2018
            [1, '2018-01-07'], // Sunday, end of week 1 2018
            [2, '2018-01-08'], // Monday, start of week 2 2018
        ];
    }

    public static function provideFirstDateOfWeekData(): array
    {
        return [
            // Week 1 scenarios
            ['2023-01-02', 2023, 1], // Monday of week 1, 2023
            ['2024-01-01', 2024, 1], // Monday of week 1, 2024 (Jan 1st is Monday)
            ['2024-12-30', 2025, 1], // Monday of week 1, 2025 (starts in Dec 2024)

            // Mid-year weeks
            ['2023-06-26', 2023, 26], // Monday of week 26, 2023
            ['2023-12-25', 2023, 52], // Monday of week 52, 2023

            // Leap weeks (week 53)
            ['2020-12-28', 2020, 53], // Monday of week 53, 2020
            ['2026-12-28', 2026, 53], // Monday of week 53, 2026

            // Edge cases where week 1 starts in previous year
            ['2024-12-30', 2025, 1], // Week 1 of 2025 starts in 2024
        ];
    }

    public static function provideYearHasWeek53Data(): array
    {
        return [
            // Years with 53 weeks (long years)
            [true, 2020],  // Leap year, Jan 1st is Wednesday
            [true, 2026],  // Regular year, Jan 1st is Thursday
            [true, 2032],  // Leap year, Jan 1st is Thursday
            [true, 2037],  // Regular year, Jan 1st is Thursday
            [true, 2043],  // Regular year, Jan 1st is Thursday

            // Years with 52 weeks
            [false, 2021], // Regular year, Jan 1st is Friday
            [false, 2022], // Regular year, Jan 1st is Saturday
            [false, 2023], // Regular year, Jan 1st is Sunday
            [false, 2024], // Leap year, Jan 1st is Monday
            [false, 2025], // Regular year, Jan 1st is Wednesday

            // Historical test cases
            [true, 2004],  // Leap year with 53 weeks
            [true, 2009],  // Regular year with 53 weeks
            [true, 2015],  // Regular year with 53 weeks
            [false, 2018], // Regular year with 52 weeks
            [false, 2019], // Regular year with 52 weeks
        ];
    }

    #[DataProvider('provideWeekBoundaryData')]
    public function testGetWeekBoundariesWithWkst(array $expected, DateTimeImmutable $date, string $weekStart): void
    {
        $boundaries = DateValidationUtils::getWeekBoundaries($date, $weekStart);

        $this->assertEquals($expected['start'], $boundaries['start']->format('Y-m-d'));
        $this->assertEquals($expected['end'], $boundaries['end']->format('Y-m-d'));
    }

    #[DataProvider('provideWeekdayOffsetData')]
    public function testGetWeekdayOffset(int $expected, string $weekday, string $weekStart): void
    {
        $offset = DateValidationUtils::getWeekdayOffset($weekday, $weekStart);

        $this->assertEquals($expected, $offset);
    }

    #[DataProvider('provideWeekdayMappingData')]
    public function testMapWeekdayToOffset(int $expected, string $weekday): void
    {
        $offset = DateValidationUtils::mapWeekdayToOffset($weekday);

        $this->assertEquals($expected, $offset);
    }

    public static function provideWeekBoundaryData(): array
    {
        return [
            // Monday as week start (default ISO)
            [
                ['start' => '2024-01-01', 'end' => '2024-01-07'],
                new DateTimeImmutable('2024-01-03'), // Wednesday
                'MO',
            ],
            [
                ['start' => '2024-01-01', 'end' => '2024-01-07'],
                new DateTimeImmutable('2024-01-01'), // Monday (week start)
                'MO',
            ],
            [
                ['start' => '2024-01-01', 'end' => '2024-01-07'],
                new DateTimeImmutable('2024-01-07'), // Sunday (week end)
                'MO',
            ],

            // Sunday as week start (US style)
            [
                ['start' => '2023-12-31', 'end' => '2024-01-06'],
                new DateTimeImmutable('2024-01-03'), // Wednesday
                'SU',
            ],
            [
                ['start' => '2023-12-31', 'end' => '2024-01-06'],
                new DateTimeImmutable('2023-12-31'), // Sunday (week start)
                'SU',
            ],
            [
                ['start' => '2023-12-31', 'end' => '2024-01-06'],
                new DateTimeImmutable('2024-01-06'), // Saturday (week end)
                'SU',
            ],

            // Tuesday as week start
            [
                ['start' => '2024-01-02', 'end' => '2024-01-08'],
                new DateTimeImmutable('2024-01-03'), // Wednesday
                'TU',
            ],
            [
                ['start' => '2024-01-02', 'end' => '2024-01-08'],
                new DateTimeImmutable('2024-01-02'), // Tuesday (week start)
                'TU',
            ],
            [
                ['start' => '2024-01-02', 'end' => '2024-01-08'],
                new DateTimeImmutable('2024-01-08'), // Monday (week end)
                'TU',
            ],

            // Friday as week start
            [
                ['start' => '2024-01-05', 'end' => '2024-01-11'],
                new DateTimeImmutable('2024-01-08'), // Monday
                'FR',
            ],
            [
                ['start' => '2024-01-05', 'end' => '2024-01-11'],
                new DateTimeImmutable('2024-01-05'), // Friday (week start)
                'FR',
            ],
            [
                ['start' => '2024-01-05', 'end' => '2024-01-11'],
                new DateTimeImmutable('2024-01-11'), // Thursday (week end)
                'FR',
            ],
        ];
    }

    public static function provideWeekdayOffsetData(): array
    {
        return [
            // Monday as week start (standard ISO offset)
            [0, 'MO', 'MO'], // Monday = 0 offset from Monday
            [1, 'TU', 'MO'], // Tuesday = 1 offset from Monday
            [2, 'WE', 'MO'], // Wednesday = 2 offset from Monday
            [3, 'TH', 'MO'], // Thursday = 3 offset from Monday
            [4, 'FR', 'MO'], // Friday = 4 offset from Monday
            [5, 'SA', 'MO'], // Saturday = 5 offset from Monday
            [6, 'SU', 'MO'], // Sunday = 6 offset from Monday

            // Sunday as week start (US style)
            [0, 'SU', 'SU'], // Sunday = 0 offset from Sunday
            [1, 'MO', 'SU'], // Monday = 1 offset from Sunday
            [2, 'TU', 'SU'], // Tuesday = 2 offset from Sunday
            [3, 'WE', 'SU'], // Wednesday = 3 offset from Sunday
            [4, 'TH', 'SU'], // Thursday = 4 offset from Sunday
            [5, 'FR', 'SU'], // Friday = 5 offset from Sunday
            [6, 'SA', 'SU'], // Saturday = 6 offset from Sunday

            // Tuesday as week start
            [0, 'TU', 'TU'], // Tuesday = 0 offset from Tuesday
            [1, 'WE', 'TU'], // Wednesday = 1 offset from Tuesday
            [2, 'TH', 'TU'], // Thursday = 2 offset from Tuesday
            [3, 'FR', 'TU'], // Friday = 3 offset from Tuesday
            [4, 'SA', 'TU'], // Saturday = 4 offset from Tuesday
            [5, 'SU', 'TU'], // Sunday = 5 offset from Tuesday
            [6, 'MO', 'TU'], // Monday = 6 offset from Tuesday

            // Friday as week start
            [0, 'FR', 'FR'], // Friday = 0 offset from Friday
            [1, 'SA', 'FR'], // Saturday = 1 offset from Friday
            [2, 'SU', 'FR'], // Sunday = 2 offset from Friday
            [3, 'MO', 'FR'], // Monday = 3 offset from Friday
            [4, 'TU', 'FR'], // Tuesday = 4 offset from Friday
            [5, 'WE', 'FR'], // Wednesday = 5 offset from Friday
            [6, 'TH', 'FR'], // Thursday = 6 offset from Friday
        ];
    }

    public static function provideWeekdayMappingData(): array
    {
        return [
            [0, 'SU'], // Sunday = 0 (matches PHP's w format)
            [1, 'MO'], // Monday = 1
            [2, 'TU'], // Tuesday = 2
            [3, 'WE'], // Wednesday = 3
            [4, 'TH'], // Thursday = 4
            [5, 'FR'], // Friday = 5
            [6, 'SA'], // Saturday = 6
        ];
    }
}
