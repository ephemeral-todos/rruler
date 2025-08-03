<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence;

use DateTimeImmutable;

final class DateValidationUtils
{
    /**
     * Get the number of days in a specific month and year.
     */
    public static function getDaysInMonth(int $year, int $month): int
    {
        return match ($month) {
            1, 3, 5, 7, 8, 10, 12 => 31,
            4, 6, 9, 11 => 30,
            2 => self::isLeapYear($year) ? 29 : 28,
            default => throw new \InvalidArgumentException("Invalid month: {$month}"),
        };
    }

    /**
     * Check if a year is a leap year.
     */
    public static function isLeapYear(int $year): bool
    {
        // A year is a leap year if:
        // - It's divisible by 4 AND
        // - If it's divisible by 100, it must also be divisible by 400
        return ($year % 4 === 0) && (($year % 100 !== 0) || ($year % 400 === 0));
    }

    /**
     * Resolve a negative day value to the corresponding positive day in the month.
     * For example, -1 in January (31 days) becomes 31, -2 becomes 30, etc.
     */
    public static function resolveNegativeDayToPositive(int $negativeDay, int $year, int $month): int
    {
        if ($negativeDay >= 0) {
            throw new \InvalidArgumentException("Day value must be negative, got: {$negativeDay}");
        }

        $daysInMonth = self::getDaysInMonth($year, $month);
        $positiveDay = $daysInMonth + $negativeDay + 1;

        if ($positiveDay < 1) {
            throw new \InvalidArgumentException(
                "Negative day value {$negativeDay} is too large for month {$month}/{$year} with {$daysInMonth} days"
            );
        }

        return $positiveDay;
    }

    /**
     * Check if a specific date (year, month, day) is valid.
     */
    public static function isValidDate(int $year, int $month, int $day): bool
    {
        if ($month < 1 || $month > 12) {
            return false;
        }

        if ($day < 1) {
            return false;
        }

        $daysInMonth = self::getDaysInMonth($year, $month);

        return $day <= $daysInMonth;
    }

    /**
     * Check if a date matches any of the BYMONTHDAY values.
     *
     * @param array<int> $byMonthDay Array of day values (positive and negative)
     */
    public static function dateMatchesByMonthDay(DateTimeImmutable $date, array $byMonthDay): bool
    {
        $year = (int) $date->format('Y');
        $month = (int) $date->format('n');
        $day = (int) $date->format('j');

        foreach ($byMonthDay as $monthDay) {
            if ($monthDay > 0) {
                // Positive day value - direct match
                if ($day === $monthDay) {
                    return true;
                }
            } else {
                // Negative day value - resolve to positive and match
                try {
                    $resolvedDay = self::resolveNegativeDayToPositive($monthDay, $year, $month);
                    if ($day === $resolvedDay) {
                        return true;
                    }
                } catch (\InvalidArgumentException) {
                    // Skip invalid negative day values for this month
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Get all valid positive day values for BYMONTHDAY in a specific month.
     * This resolves negative values and filters out invalid dates.
     *
     * @param array<int> $byMonthDay Array of day values (positive and negative)
     * @return array<int> Array of valid positive day values
     */
    public static function getValidDaysForMonth(array $byMonthDay, int $year, int $month): array
    {
        $validDays = [];
        $daysInMonth = self::getDaysInMonth($year, $month);

        foreach ($byMonthDay as $monthDay) {
            if ($monthDay > 0) {
                // Positive day value - include if valid for this month
                if ($monthDay <= $daysInMonth) {
                    $validDays[] = $monthDay;
                }
            } else {
                // Negative day value - resolve to positive if valid
                try {
                    $resolvedDay = self::resolveNegativeDayToPositive($monthDay, $year, $month);
                    $validDays[] = $resolvedDay;
                } catch (\InvalidArgumentException) {
                    // Skip invalid negative day values for this month
                    continue;
                }
            }
        }

        // Remove duplicates and sort
        $validDays = array_unique($validDays);
        sort($validDays);

        return $validDays;
    }
}
