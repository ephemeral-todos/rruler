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
}
