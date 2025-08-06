<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\DateValidationUtils;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use Generator;

final class DefaultOccurrenceGenerator implements OccurrenceGenerator
{
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): Generator {
        // For BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO rules, find the first valid occurrence from start date
        $current = ($rrule->hasByDay() || $rrule->hasByMonthDay() || $rrule->hasByMonth() || $rrule->hasByWeekNo()) ? $this->findFirstValidOccurrence($rrule, $start) : $start;
        $count = 0;
        $maxCount = $limit ?? $rrule->getCount();

        // Early termination for COUNT=0
        if ($maxCount === 0) {
            return;
        }

        while (true) {
            // Check UNTIL condition
            if ($rrule->hasUntil() && $current > $rrule->getUntil()) {
                break;
            }

            yield $current;
            ++$count;

            // Check COUNT condition
            if ($maxCount !== null && $count >= $maxCount) {
                break;
            }

            $current = $this->getNextOccurrence($rrule, $current);
        }
    }

    public function generateOccurrencesInRange(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
    ): Generator {
        foreach ($this->generateOccurrences($rrule, $start) as $occurrence) {
            if ($occurrence < $rangeStart) {
                continue;
            }

            if ($occurrence > $rangeEnd) {
                break;
            }

            yield $occurrence;
        }
    }

    private function getNextOccurrence(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        if ($rrule->hasByDay()) {
            return $this->getNextOccurrenceWithByDay($rrule, $current);
        }

        if ($rrule->hasByMonthDay()) {
            return $this->getNextOccurrenceWithByMonthDay($rrule, $current);
        }

        if ($rrule->hasByMonth()) {
            return $this->getNextOccurrenceWithByMonth($rrule, $current);
        }

        if ($rrule->hasByWeekNo()) {
            return $this->getNextOccurrenceWithByWeekNo($rrule, $current);
        }

        $interval = $rrule->getInterval();

        return match ($rrule->getFrequency()) {
            'DAILY' => $current->modify("+{$interval} days"),
            'WEEKLY' => $current->modify("+{$interval} weeks"),
            'MONTHLY' => $current->modify("+{$interval} months"),
            'YEARLY' => $current->modify("+{$interval} years"),
            default => throw new \InvalidArgumentException("Unsupported frequency: {$rrule->getFrequency()}"),
        };
    }

    private function getNextOccurrenceWithByDay(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        $frequency = $rrule->getFrequency();
        $interval = $rrule->getInterval();
        $byDay = $rrule->getByDay();

        if ($byDay === null) {
            throw new \LogicException('BYDAY data is null when hasByDay() returned true');
        }

        return match ($frequency) {
            'DAILY' => $this->getNextDailyByDay($current, $byDay, $interval),
            'WEEKLY' => $this->getNextWeeklyByDay($current, $byDay, $interval),
            'MONTHLY' => $this->getNextMonthlyByDay($current, $byDay, $interval),
            'YEARLY' => $this->getNextYearlyByDay($current, $byDay, $interval),
            default => throw new \InvalidArgumentException("Unsupported frequency: {$frequency}"),
        };
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function getNextDailyByDay(DateTimeImmutable $current, array $byDay, int $interval): DateTimeImmutable
    {
        // For DAILY with BYDAY, we need to find the next day that matches one of the weekdays
        $validWeekdays = array_column($byDay, 'weekday');
        $candidate = $current->modify('+1 day');

        while (true) {
            $weekday = $this->getWeekdayFromDate($candidate);
            if (in_array($weekday, $validWeekdays, true)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function getNextWeeklyByDay(DateTimeImmutable $current, array $byDay, int $interval): DateTimeImmutable
    {
        $validWeekdays = array_column($byDay, 'weekday');

        // Find next weekday in the same week
        $candidate = $current->modify('+1 day');
        $weekStart = $current->modify('monday this week');
        $weekEnd = $weekStart->modify('+6 days');

        while ($candidate <= $weekEnd) {
            $weekday = $this->getWeekdayFromDate($candidate);
            if (in_array($weekday, $validWeekdays, true)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next interval week and find first matching weekday
        $nextWeekStart = $weekStart->modify("+{$interval} weeks");

        return $this->findFirstMatchingWeekdayInWeek($nextWeekStart, $validWeekdays);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function getNextMonthlyByDay(DateTimeImmutable $current, array $byDay, int $interval): DateTimeImmutable
    {
        // For monthly, we need to handle both positional (1MO, -1FR) and non-positional (MO,WE,FR)
        $candidate = $current->modify('+1 day');
        $currentMonth = $current->format('Y-m');

        // First, try to find next occurrence in same month
        while ($candidate->format('Y-m') === $currentMonth) {
            if ($this->dateMatchesMonthlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next interval month and find first matching day
        $nextMonth = $current->modify("first day of +{$interval} month");

        return $this->findFirstMatchingDayInMonth($nextMonth, $byDay);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function getNextYearlyByDay(DateTimeImmutable $current, array $byDay, int $interval): DateTimeImmutable
    {
        // Similar to monthly but across the entire year
        $candidate = $current->modify('+1 day');
        $currentYear = $current->format('Y');

        while ($candidate->format('Y') === $currentYear) {
            if ($this->dateMatchesYearlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next interval year
        $nextYear = $current->modify("first day of January +{$interval} year");

        return $this->findFirstMatchingDayInYear($nextYear, $byDay);
    }

    private function getWeekdayFromDate(DateTimeImmutable $date): string
    {
        return match ($date->format('N')) {
            '1' => 'MO',
            '2' => 'TU',
            '3' => 'WE',
            '4' => 'TH',
            '5' => 'FR',
            '6' => 'SA',
            '7' => 'SU',
        };
    }

    /**
     * @param array<string> $validWeekdays
     */
    private function findFirstMatchingWeekdayInWeek(DateTimeImmutable $weekStart, array $validWeekdays): DateTimeImmutable
    {
        $candidate = $weekStart;
        for ($i = 0; $i < 7; ++$i) {
            $weekday = $this->getWeekdayFromDate($candidate);
            if (in_array($weekday, $validWeekdays, true)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        throw new \RuntimeException('No matching weekday found in week');
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function dateMatchesMonthlyByDay(DateTimeImmutable $date, array $byDay): bool
    {
        $weekday = $this->getWeekdayFromDate($date);

        foreach ($byDay as $daySpec) {
            if ($daySpec['weekday'] !== $weekday) {
                continue;
            }

            if ($daySpec['position'] === null) {
                return true; // Any occurrence of this weekday
            }

            if ($this->dateMatchesPosition($date, $daySpec['position'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function dateMatchesYearlyByDay(DateTimeImmutable $date, array $byDay): bool
    {
        // For yearly, we need to check if date matches any of the BYDAY specifications within the year
        // This is complex as it involves checking all possible interpretations
        // For now, simplified version that treats it like monthly within each month
        return $this->dateMatchesMonthlyByDay($date, $byDay);
    }

    private function dateMatchesPosition(DateTimeImmutable $date, int $position): bool
    {
        $weekday = $this->getWeekdayFromDate($date);
        $monthStart = $date->modify('first day of this month');
        $monthEnd = $date->modify('last day of this month');

        if ($position > 0) {
            // Positive position: count from beginning of month
            $candidate = $monthStart;
            $occurrenceCount = 0;

            while ($candidate <= $monthEnd) {
                if ($this->getWeekdayFromDate($candidate) === $weekday) {
                    ++$occurrenceCount;
                    if ($occurrenceCount === $position) {
                        return $candidate->format('Y-m-d') === $date->format('Y-m-d');
                    }
                }
                $candidate = $candidate->modify('+1 day');
            }
        } else {
            // Negative position: count from end of month
            $candidate = $monthEnd;
            $occurrenceCount = 0;
            $targetPosition = abs($position);

            while ($candidate >= $monthStart) {
                if ($this->getWeekdayFromDate($candidate) === $weekday) {
                    ++$occurrenceCount;
                    if ($occurrenceCount === $targetPosition) {
                        return $candidate->format('Y-m-d') === $date->format('Y-m-d');
                    }
                }
                $candidate = $candidate->modify('-1 day');
            }
        }

        return false;
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findFirstMatchingDayInMonth(DateTimeImmutable $monthStart, array $byDay): DateTimeImmutable
    {
        $monthEnd = $monthStart->modify('last day of this month');
        $candidate = $monthStart;

        while ($candidate <= $monthEnd) {
            if ($this->dateMatchesMonthlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        throw new \RuntimeException('No matching day found in month');
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findFirstMatchingDayInYear(DateTimeImmutable $yearStart, array $byDay): DateTimeImmutable
    {
        $yearEnd = $yearStart->modify('last day of December this year');
        $candidate = $yearStart;

        while ($candidate <= $yearEnd) {
            if ($this->dateMatchesYearlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        throw new \RuntimeException('No matching day found in year');
    }

    private function findFirstValidOccurrence(Rrule $rrule, DateTimeImmutable $start): DateTimeImmutable
    {
        $frequency = $rrule->getFrequency();

        // Handle BYDAY rules
        if ($rrule->hasByDay()) {
            $byDay = $rrule->getByDay();

            if ($byDay === null) {
                throw new \LogicException('BYDAY data is null when hasByDay() returned true');
            }

            // Check if start date itself is valid
            if ($this->isDateValidForByDay($start, $frequency, $byDay)) {
                return $start;
            }

            // Find the first valid date after start
            return match ($frequency) {
                'DAILY' => $this->findNextDailyByDay($start, $byDay),
                'WEEKLY' => $this->findNextWeeklyByDay($start, $byDay),
                'MONTHLY' => $this->findNextMonthlyByDay($start, $byDay),
                'YEARLY' => $this->findNextYearlyByDay($start, $byDay),
                default => throw new \InvalidArgumentException("Unsupported frequency: {$frequency}"),
            };
        }

        // Handle BYMONTHDAY rules
        if ($rrule->hasByMonthDay()) {
            $byMonthDay = $rrule->getByMonthDay();

            if ($byMonthDay === null) {
                throw new \LogicException('BYMONTHDAY data is null when hasByMonthDay() returned true');
            }

            // Check if start date itself is valid
            if (DateValidationUtils::dateMatchesByMonthDay($start, $byMonthDay)) {
                return $start;
            }

            // Find the first valid date after start
            return match ($frequency) {
                'MONTHLY' => $this->findNextMonthlyByMonthDay($start, $byMonthDay),
                'YEARLY' => $this->findNextYearlyByMonthDay($start, $byMonthDay),
                default => throw new \InvalidArgumentException("BYMONTHDAY is not supported for frequency: {$frequency}"),
            };
        }

        // Handle BYMONTH rules
        if ($rrule->hasByMonth()) {
            $byMonth = $rrule->getByMonth();

            if ($byMonth === null) {
                throw new \LogicException('BYMONTH data is null when hasByMonth() returned true');
            }

            // Check if start date itself is valid
            if ($this->dateMatchesByMonth($start, $byMonth)) {
                return $start;
            }

            // Find the first valid date after start
            return match ($frequency) {
                'YEARLY' => $this->findNextYearlyByMonth($start, $byMonth),
                default => throw new \InvalidArgumentException("BYMONTH is not supported for frequency: {$frequency}"),
            };
        }

        // Handle BYWEEKNO rules
        if ($rrule->hasByWeekNo()) {
            $byWeekNo = $rrule->getByWeekNo();

            if ($byWeekNo === null) {
                throw new \LogicException('BYWEEKNO data is null when hasByWeekNo() returned true');
            }

            // Check if start date itself is valid
            if ($this->dateMatchesByWeekNo($start, $byWeekNo)) {
                return $start;
            }

            // Find the first valid date after start
            return match ($frequency) {
                'YEARLY' => $this->findNextYearlyByWeekNo($start, $byWeekNo),
                default => throw new \InvalidArgumentException("BYWEEKNO is not supported for frequency: {$frequency}"),
            };
        }

        return $start;
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function findNextMonthlyByMonthDay(DateTimeImmutable $start, array $byMonthDay): DateTimeImmutable
    {
        $candidate = $start->modify('+1 day');
        $currentMonth = $start->format('Y-m');

        // Look in same month first
        while ($candidate->format('Y-m') === $currentMonth) {
            if (DateValidationUtils::dateMatchesByMonthDay($candidate, $byMonthDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next month
        $nextMonth = $start->modify('first day of next month');

        return $this->findFirstMatchingByMonthDayInMonth($nextMonth, $byMonthDay);
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function findNextYearlyByMonthDay(DateTimeImmutable $start, array $byMonthDay): DateTimeImmutable
    {
        // For yearly frequency, BYMONTHDAY should apply to the same month each year
        $currentMonth = (int) $start->format('n');
        $currentDay = (int) $start->format('j');

        // Find next occurrence in the same month of the same year
        $year = (int) $start->format('Y');
        $validDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $year, $currentMonth);

        // Look for a valid day after the current day in the same month
        foreach ($validDays as $validDay) {
            if ($validDay > $currentDay) {
                return $start->setDate($year, $currentMonth, $validDay);
            }
        }

        // No valid days remaining in this month of this year, move to next year
        $nextYear = $year + 1;
        $nextYearValidDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $nextYear, $currentMonth);

        if (empty($nextYearValidDays)) {
            throw new \RuntimeException("No valid BYMONTHDAY values for {$nextYear}-{$currentMonth}");
        }

        // Return the first valid day in the same month of the next year
        $firstDay = $nextYearValidDays[0];

        return $start->setDate($nextYear, $currentMonth, $firstDay);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function isDateValidForByDay(DateTimeImmutable $date, string $frequency, array $byDay): bool
    {
        return match ($frequency) {
            'DAILY', 'WEEKLY' => $this->dateMatchesWeekdayList($date, $byDay),
            'MONTHLY' => $this->dateMatchesMonthlyByDay($date, $byDay),
            'YEARLY' => $this->dateMatchesYearlyByDay($date, $byDay),
            default => false,
        };
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function dateMatchesWeekdayList(DateTimeImmutable $date, array $byDay): bool
    {
        $weekday = $this->getWeekdayFromDate($date);
        $validWeekdays = array_column($byDay, 'weekday');

        return in_array($weekday, $validWeekdays, true);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findNextDailyByDay(DateTimeImmutable $start, array $byDay): DateTimeImmutable
    {
        $validWeekdays = array_column($byDay, 'weekday');
        $candidate = $start->modify('+1 day');

        while (true) {
            $weekday = $this->getWeekdayFromDate($candidate);
            if (in_array($weekday, $validWeekdays, true)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findNextWeeklyByDay(DateTimeImmutable $start, array $byDay): DateTimeImmutable
    {
        $validWeekdays = array_column($byDay, 'weekday');
        $candidate = $start->modify('+1 day');
        $weekStart = $start->modify('monday this week');
        $weekEnd = $weekStart->modify('+6 days');

        // Look for next valid day in same week
        while ($candidate <= $weekEnd) {
            $weekday = $this->getWeekdayFromDate($candidate);
            if (in_array($weekday, $validWeekdays, true)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next week
        $nextWeekStart = $weekStart->modify('+1 week');

        return $this->findFirstMatchingWeekdayInWeek($nextWeekStart, $validWeekdays);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findNextMonthlyByDay(DateTimeImmutable $start, array $byDay): DateTimeImmutable
    {
        $candidate = $start->modify('+1 day');
        $currentMonth = $start->format('Y-m');

        // Look in same month first
        while ($candidate->format('Y-m') === $currentMonth) {
            if ($this->dateMatchesMonthlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next month
        $nextMonth = $start->modify('first day of next month');

        return $this->findFirstMatchingDayInMonth($nextMonth, $byDay);
    }

    /**
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function findNextYearlyByDay(DateTimeImmutable $start, array $byDay): DateTimeImmutable
    {
        $candidate = $start->modify('+1 day');
        $currentYear = $start->format('Y');

        while ($candidate->format('Y') === $currentYear) {
            if ($this->dateMatchesYearlyByDay($candidate, $byDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next year
        $nextYear = $start->modify('first day of January next year');

        return $this->findFirstMatchingDayInYear($nextYear, $byDay);
    }

    private function getNextOccurrenceWithByMonthDay(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        $frequency = $rrule->getFrequency();
        $interval = $rrule->getInterval();
        $byMonthDay = $rrule->getByMonthDay();

        if ($byMonthDay === null) {
            throw new \LogicException('BYMONTHDAY data is null when hasByMonthDay() returned true');
        }

        return match ($frequency) {
            'MONTHLY' => $this->getNextMonthlyByMonthDay($current, $byMonthDay, $interval),
            'YEARLY' => $this->getNextYearlyByMonthDay($current, $byMonthDay, $interval),
            default => throw new \InvalidArgumentException("BYMONTHDAY is not supported for frequency: {$frequency}"),
        };
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function getNextMonthlyByMonthDay(DateTimeImmutable $current, array $byMonthDay, int $interval): DateTimeImmutable
    {
        // Find next occurrence in the same month first
        $candidate = $current->modify('+1 day');
        $currentMonth = $current->format('Y-m');

        while ($candidate->format('Y-m') === $currentMonth) {
            if (DateValidationUtils::dateMatchesByMonthDay($candidate, $byMonthDay)) {
                return $candidate;
            }
            $candidate = $candidate->modify('+1 day');
        }

        // Move to next interval month and find first matching day
        $nextMonth = $current->modify("first day of +{$interval} month");

        return $this->findFirstMatchingByMonthDayInMonthOrNext($nextMonth, $byMonthDay, $interval);
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function getNextYearlyByMonthDay(DateTimeImmutable $current, array $byMonthDay, int $interval): DateTimeImmutable
    {
        // For yearly frequency, BYMONTHDAY should apply to the same month each year
        $currentMonth = (int) $current->format('n');
        $currentDay = (int) $current->format('j');

        // Find next occurrence in the same month of the same year
        $year = (int) $current->format('Y');
        $validDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $year, $currentMonth);

        // Look for a valid day after the current day in the same month
        foreach ($validDays as $validDay) {
            if ($validDay > $currentDay) {
                return $current->setDate($year, $currentMonth, $validDay);
            }
        }

        // No valid days remaining in this month of this year, move to next interval year
        $nextYear = $year + $interval;
        $nextYearValidDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $nextYear, $currentMonth);

        if (empty($nextYearValidDays)) {
            throw new \RuntimeException("No valid BYMONTHDAY values for {$nextYear}-{$currentMonth}");
        }

        // Return the first valid day in the same month of the next interval year
        $firstDay = $nextYearValidDays[0];

        return $current->setDate($nextYear, $currentMonth, $firstDay);
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function findFirstMatchingByMonthDayInMonth(DateTimeImmutable $monthStart, array $byMonthDay): DateTimeImmutable
    {
        $year = (int) $monthStart->format('Y');
        $month = (int) $monthStart->format('n');
        $validDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $year, $month);

        if (empty($validDays)) {
            throw new \RuntimeException("No valid BYMONTHDAY values for {$year}-{$month}");
        }

        // Return the first valid day in the month
        $firstDay = $validDays[0];

        return $monthStart->setDate($year, $month, $firstDay);
    }

    /**
     * @param array<int> $byMonthDay
     */
    private function findFirstMatchingByMonthDayInMonthOrNext(DateTimeImmutable $monthStart, array $byMonthDay, int $interval): DateTimeImmutable
    {
        $current = $monthStart;

        // Try up to 12 months to avoid infinite loops
        for ($attempts = 0; $attempts < 12; ++$attempts) {
            $year = (int) $current->format('Y');
            $month = (int) $current->format('n');
            $validDays = DateValidationUtils::getValidDaysForMonth($byMonthDay, $year, $month);

            if (!empty($validDays)) {
                $firstDay = $validDays[0];

                return $current->setDate($year, $month, $firstDay);
            }

            // No valid days in this month, move to next interval month
            $current = $current->modify("first day of +{$interval} month");
        }

        throw new \RuntimeException('No valid BYMONTHDAY values found in any month');
    }

    private function getNextOccurrenceWithByMonth(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        $frequency = $rrule->getFrequency();
        $interval = $rrule->getInterval();
        $byMonth = $rrule->getByMonth();

        if ($byMonth === null) {
            throw new \LogicException('BYMONTH data is null when hasByMonth() returned true');
        }

        return match ($frequency) {
            'YEARLY' => $this->getNextYearlyByMonth($current, $byMonth, $interval),
            default => throw new \InvalidArgumentException("BYMONTH is not supported for frequency: {$frequency}"),
        };
    }

    /**
     * @param array<int> $byMonth
     */
    private function getNextYearlyByMonth(DateTimeImmutable $current, array $byMonth, int $interval): DateTimeImmutable
    {
        $currentMonth = (int) $current->format('n');
        $currentDay = (int) $current->format('j');
        $currentYear = (int) $current->format('Y');

        // Sort months to find next valid month
        $sortedMonths = $byMonth;
        sort($sortedMonths);

        // Look for a valid month after the current month in the same year
        foreach ($sortedMonths as $month) {
            if ($month > $currentMonth) {
                // Found a valid month later in the year
                return $current->setDate($currentYear, $month, $currentDay);
            }
        }

        // No valid months remaining in this year, move to next interval year
        $nextYear = $currentYear + $interval;
        $firstMonth = $sortedMonths[0]; // Use first month from sorted BYMONTH list

        return $current->setDate($nextYear, $firstMonth, $currentDay);
    }

    /**
     * @param array<int> $byMonth
     */
    private function dateMatchesByMonth(DateTimeImmutable $date, array $byMonth): bool
    {
        $month = (int) $date->format('n');

        return in_array($month, $byMonth, true);
    }

    /**
     * @param array<int> $byMonth
     */
    private function findNextYearlyByMonth(DateTimeImmutable $start, array $byMonth): DateTimeImmutable
    {
        $currentMonth = (int) $start->format('n');
        $currentDay = (int) $start->format('j');
        $currentYear = (int) $start->format('Y');

        // Look for a valid month after the current month in the same year
        foreach ($byMonth as $month) {
            if ($month > $currentMonth) {
                // Found a valid month later in the year
                return $start->setDate($currentYear, $month, $currentDay);
            }
        }

        // No valid months remaining in this year, move to next year
        $nextYear = $currentYear + 1;
        $firstMonth = $byMonth[0]; // Use first month from BYMONTH list

        return $start->setDate($nextYear, $firstMonth, $currentDay);
    }

    private function getNextOccurrenceWithByWeekNo(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        $frequency = $rrule->getFrequency();
        $interval = $rrule->getInterval();
        $byWeekNo = $rrule->getByWeekNo();

        if ($byWeekNo === null) {
            throw new \LogicException('BYWEEKNO data is null when hasByWeekNo() returned true');
        }

        return match ($frequency) {
            'YEARLY' => $this->getNextYearlyByWeekNo($current, $byWeekNo, $interval),
            default => throw new \InvalidArgumentException("BYWEEKNO is not supported for frequency: {$frequency}"),
        };
    }

    /**
     * @param array<int> $byWeekNo
     */
    private function getNextYearlyByWeekNo(DateTimeImmutable $current, array $byWeekNo, int $interval): DateTimeImmutable
    {
        $currentYear = (int) $current->format('Y');
        $currentWeek = DateValidationUtils::getIsoWeekNumber($current);
        $currentDayOfWeek = (int) $current->format('N'); // 1=Monday, 7=Sunday

        // Sort week numbers to find next valid week
        $sortedWeeks = $byWeekNo;
        sort($sortedWeeks);

        // Look for a valid week after the current week in the same year
        foreach ($sortedWeeks as $week) {
            if ($week > $currentWeek) {
                // Found a valid week later in the year
                $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($currentYear, $week);
                // Preserve the day of week from current date
                return $mondayOfWeek->modify('+' . ($currentDayOfWeek - 1) . ' days');
            }
        }

        // No valid weeks remaining in this year, move to next interval year
        $nextYear = $currentYear + $interval;
        $firstWeek = $sortedWeeks[0]; // Use first week from sorted BYWEEKNO list

        // Handle leap weeks - if week 53 doesn't exist in target year, find next year that has it
        while ($firstWeek === 53 && !DateValidationUtils::yearHasWeek53($nextYear)) {
            $nextYear += $interval;
        }

        $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($nextYear, $firstWeek);
        // Preserve the day of week from current date
        return $mondayOfWeek->modify('+' . ($currentDayOfWeek - 1) . ' days');
    }

    /**
     * @param array<int> $byWeekNo
     */
    private function dateMatchesByWeekNo(DateTimeImmutable $date, array $byWeekNo): bool
    {
        $weekNumber = DateValidationUtils::getIsoWeekNumber($date);

        return in_array($weekNumber, $byWeekNo, true);
    }

    /**
     * @param array<int> $byWeekNo
     */
    private function findNextYearlyByWeekNo(DateTimeImmutable $start, array $byWeekNo): DateTimeImmutable
    {
        $currentYear = (int) $start->format('Y');
        $currentWeek = DateValidationUtils::getIsoWeekNumber($start);
        $currentDayOfWeek = (int) $start->format('N');

        // Sort week numbers to find next valid week
        $sortedWeeks = $byWeekNo;
        sort($sortedWeeks);

        // Look for a valid week after the current week in the same year
        foreach ($sortedWeeks as $week) {
            if ($week > $currentWeek) {
                // Found a valid week later in the year
                $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($currentYear, $week);
                // Preserve the day of week from start date
                return $mondayOfWeek->modify('+' . ($currentDayOfWeek - 1) . ' days');
            }
        }

        // No valid weeks remaining in this year, move to next year
        $nextYear = $currentYear + 1;
        $firstWeek = $sortedWeeks[0]; // Use first week from BYWEEKNO list

        // Handle leap weeks - if week 53 doesn't exist in target year, find next year that has it
        while ($firstWeek === 53 && !DateValidationUtils::yearHasWeek53($nextYear)) {
            $nextYear += 1;
        }

        $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($nextYear, $firstWeek);
        // Preserve the day of week from start date
        return $mondayOfWeek->modify('+' . ($currentDayOfWeek - 1) . ' days');
    }
}
