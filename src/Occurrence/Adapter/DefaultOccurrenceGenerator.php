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
    /**
     * @return Generator<DateTimeImmutable>
     */
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): Generator {
        // BYSETPOS requires a different approach - expand periods then select positions
        if ($rrule->hasBySetPos()) {
            yield from $this->generateOccurrencesWithBySetPos($rrule, $start, $limit);

            return;
        }

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
        $currentYear = (int) $current->format('o'); // Use ISO week year, not calendar year
        $currentWeek = DateValidationUtils::getIsoWeekNumber($current);
        $currentDayOfWeek = (int) $current->format('N'); // 1=Monday, 7=Sunday

        // Sort week numbers to find next valid week
        $sortedWeeks = $byWeekNo;
        sort($sortedWeeks);

        // Look for a valid week after the current week in the same year
        foreach ($sortedWeeks as $week) {
            if ($week > $currentWeek) {
                // Check if this week exists in the current year (important for week 53)
                if ($week === 53 && !DateValidationUtils::yearHasWeek53($currentYear)) {
                    continue; // Skip week 53 if it doesn't exist in current year
                }

                // Found a valid week later in the year
                $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($currentYear, $week);

                // Preserve the day of week from current date
                return $mondayOfWeek->modify('+'.($currentDayOfWeek - 1).' days');
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
        return $mondayOfWeek->modify('+'.($currentDayOfWeek - 1).' days');
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
        $currentYear = (int) $start->format('o'); // Use ISO week year, not calendar year
        $currentWeek = DateValidationUtils::getIsoWeekNumber($start);
        $currentDayOfWeek = (int) $start->format('N');

        // Sort week numbers to find next valid week
        $sortedWeeks = $byWeekNo;
        sort($sortedWeeks);

        // Look for a valid week after the current week in the same year
        foreach ($sortedWeeks as $week) {
            if ($week > $currentWeek) {
                // Check if this week exists in the current year (important for week 53)
                if ($week === 53 && !DateValidationUtils::yearHasWeek53($currentYear)) {
                    continue; // Skip week 53 if it doesn't exist in current year
                }

                // Found a valid week later in the year
                $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($currentYear, $week);

                // Preserve the day of week from start date
                return $mondayOfWeek->modify('+'.($currentDayOfWeek - 1).' days');
            }
        }

        // No valid weeks remaining in this year, move to next year
        $nextYear = $currentYear + 1;
        $firstWeek = $sortedWeeks[0]; // Use first week from BYWEEKNO list

        // Handle leap weeks - if week 53 doesn't exist in target year, find next year that has it
        while ($firstWeek === 53 && !DateValidationUtils::yearHasWeek53($nextYear)) {
            ++$nextYear;
        }

        $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($nextYear, $firstWeek);

        // Preserve the day of week from start date
        return $mondayOfWeek->modify('+'.($currentDayOfWeek - 1).' days');
    }

    /**
     * Generate occurrences when BYSETPOS is present.
     * Uses two-phase approach: expand all potential occurrences in a period, then select by position.
     *
     * @return Generator<DateTimeImmutable>
     */
    private function generateOccurrencesWithBySetPos(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): Generator {
        $count = 0;
        $maxCount = $limit ?? $rrule->getCount();

        // Early termination for COUNT=0
        if ($maxCount === 0) {
            return;
        }

        // BYSETPOS requires other BY* rules to work with
        if (!$this->hasExpandableByRules($rrule)) {
            // If no expandable BY* rules, BYSETPOS has no effect - fall back to normal generation
            $tempRrule = $this->createRruleWithoutBySetPos($rrule);
            yield from $this->generateOccurrencesWithoutBySetPos($tempRrule, $start, $limit);

            return;
        }

        $currentPeriod = $this->findStartingPeriod($rrule, $start);
        $periodsWithoutOccurrences = 0;
        $maxEmptyPeriods = 50; // Safety limit to prevent infinite loops

        while (true) {
            // For BYMONTH patterns, we need to process each month as a separate sub-period for BYSETPOS
            if ($rrule->hasByMonth() && $rrule->getFrequency() === 'YEARLY') {
                $monthlyPeriodResults = $this->processYearlyByMonthPeriods($rrule, $currentPeriod, $start, $maxCount, $count);

                foreach ($monthlyPeriodResults as $occurrence) {
                    // Check UNTIL condition
                    if ($rrule->hasUntil() && $occurrence > $rrule->getUntil()) {
                        return;
                    }

                    yield $occurrence;
                    ++$count;

                    // Check COUNT condition
                    if ($maxCount !== null && $count >= $maxCount) {
                        return;
                    }
                }
            } else {
                // Standard period processing
                // For the first period, handle start date filtering differently
                $isFirstPeriod = $currentPeriod->format('Y-m-d H:i:s') === $this->findStartingPeriod($rrule, $start)->format('Y-m-d H:i:s');

                // Expand all occurrences within this period
                $expandedOccurrences = $this->expandOccurrencesInPeriod($rrule, $currentPeriod);

                // For first period, pre-filter by start date before BYSETPOS to avoid missing valid selections
                if ($isFirstPeriod) {
                    $expandedOccurrences = array_filter($expandedOccurrences, fn ($occ) => $occ >= $start);
                }

                // Apply BYSETPOS to select specific positions (unless already applied in expansion)
                if ($rrule->hasByWeekNo()) {
                    // BYSETPOS already applied in expandByWeekNoWithBySetPos
                    $selectedOccurrences = $expandedOccurrences;
                } else {
                    $selectedOccurrences = $this->applyBySetPosSelection($expandedOccurrences, $rrule->getBySetPos() ?? []);
                }

                // Track whether any occurrences were yielded in this period
                $periodHadOccurrences = false;

                // Yield selected occurrences
                foreach ($selectedOccurrences as $occurrence) {
                    // For non-first periods, still apply start date filter
                    if (!$isFirstPeriod && $occurrence < $start) {
                        continue;
                    }

                    // Check UNTIL condition
                    if ($rrule->hasUntil() && $occurrence > $rrule->getUntil()) {
                        return;
                    }

                    yield $occurrence;
                    ++$count;
                    $periodHadOccurrences = true;

                    // Check COUNT condition
                    if ($maxCount !== null && $count >= $maxCount) {
                        return;
                    }
                }

                // Safety check: if no occurrences for many consecutive periods, break to prevent infinite loops
                if (!$periodHadOccurrences) {
                    ++$periodsWithoutOccurrences;
                    if ($periodsWithoutOccurrences >= $maxEmptyPeriods) {
                        // This BYSETPOS configuration never produces occurrences - terminate
                        return;
                    }
                } else {
                    $periodsWithoutOccurrences = 0; // Reset counter when occurrences are found
                }
            }

            // Move to next period
            $currentPeriod = $this->getNextPeriod($rrule, $currentPeriod);
        }
    }

    /**
     * Check if the RRULE has BY* rules that can be expanded for BYSETPOS.
     */
    private function hasExpandableByRules(Rrule $rrule): bool
    {
        return $rrule->hasByDay() || $rrule->hasByMonthDay() || $rrule->hasByMonth() || $rrule->hasByWeekNo();
    }

    /**
     * Create a copy of the RRULE without BYSETPOS for fallback generation.
     */
    private function createRruleWithoutBySetPos(Rrule $rrule): Rrule
    {
        return new Rrule(
            $rrule->getFrequency(),
            $rrule->getInterval(),
            $rrule->getCount(),
            $rrule->getUntil(),
            $rrule->getByDay(),
            $rrule->getByMonthDay(),
            $rrule->getByMonth(),
            $rrule->getByWeekNo(),
            null // Remove BYSETPOS
        );
    }

    /**
     * Find the starting period for BYSETPOS expansion.
     */
    private function findStartingPeriod(Rrule $rrule, DateTimeImmutable $start): DateTimeImmutable
    {
        // The period start depends on the frequency
        // For BYSETPOS, we need to ensure the period includes the start date
        return match ($rrule->getFrequency()) {
            'YEARLY' => $start->modify('first day of January')->setTime(0, 0, 0),
            'MONTHLY' => $start->modify('first day of this month')->setTime(0, 0, 0),
            'WEEKLY' => $this->findWeeklyStartingPeriod($start),
            'DAILY' => $start->setTime(0, 0, 0), // For daily, each day is its own period
            default => throw new \InvalidArgumentException("Unsupported frequency for BYSETPOS: {$rrule->getFrequency()}"),
        };
    }

    /**
     * Find the appropriate starting period for weekly BYSETPOS patterns.
     * This ensures we don't miss occurrences in the week containing the start date.
     */
    private function findWeeklyStartingPeriod(DateTimeImmutable $start): DateTimeImmutable
    {
        $weekStart = $start->modify('Monday this week')->setTime(0, 0, 0);

        // If the start date is at the beginning of the week (Monday-Tuesday),
        // use this week. Otherwise, check if we should include this week or start from next week.
        $dayOfWeek = (int) $start->format('N'); // 1=Monday, 7=Sunday

        // Always start from the week containing the start date to ensure we don't miss occurrences
        return $weekStart;
    }

    /**
     * Expand all potential occurrences within a given period.
     *
     * @return array<DateTimeImmutable>
     */
    private function expandOccurrencesInPeriod(Rrule $rrule, DateTimeImmutable $periodStart): array
    {
        $occurrences = [];
        $periodEnd = $this->getPeriodEnd($rrule, $periodStart);

        // Create a temporary RRULE without BYSETPOS to expand occurrences within the period
        $tempRrule = $this->createRruleWithoutBySetPos($rrule);

        // For BYSETPOS expansion, we need to find ALL matching dates in the period
        // Use specialized expansion based on the BY* rules present
        if ($rrule->hasByWeekNo()) {
            // For BYWEEKNO with BYSETPOS, each week should be treated as a separate sub-period
            $occurrences = $this->expandByWeekNoWithBySetPos($rrule, $periodStart, $periodEnd);
        } elseif ($rrule->hasByMonth()) {
            // For BYMONTH, expand by checking each specified month in the period
            $occurrences = $this->expandByMonthInPeriod($rrule, $periodStart, $periodEnd);
        } else {
            // For other BY* rules, use day-by-day expansion
            $current = $periodStart;
            while ($current <= $periodEnd) {
                if ($this->dateMatchesRrule($tempRrule, $current)) {
                    $occurrences[] = $current;
                }
                $current = $current->modify('+1 day');
            }
        }

        return $occurrences;
    }

    /**
     * Get the end date of the current period.
     */
    private function getPeriodEnd(Rrule $rrule, DateTimeImmutable $periodStart): DateTimeImmutable
    {
        return match ($rrule->getFrequency()) {
            'YEARLY' => $periodStart->modify('last day of December 23:59:59'),
            'MONTHLY' => $periodStart->modify('last day of this month 23:59:59'),
            'WEEKLY' => $periodStart->modify('Sunday this week 23:59:59'),
            'DAILY' => $periodStart->setTime(23, 59, 59), // End of the same day
            default => throw new \InvalidArgumentException("Unsupported frequency for BYSETPOS: {$rrule->getFrequency()}"),
        };
    }

    /**
     * Check if a date matches the RRULE criteria (without BYSETPOS).
     */
    private function dateMatchesRrule(Rrule $rrule, DateTimeImmutable $date): bool
    {
        // Check BY* rules with null safety
        $byDay = $rrule->getByDay();
        if ($rrule->hasByDay() && $byDay !== null && !$this->dateMatchesByDayGeneric($rrule, $date, $byDay)) {
            return false;
        }

        $byMonthDay = $rrule->getByMonthDay();
        if ($rrule->hasByMonthDay() && $byMonthDay !== null && !DateValidationUtils::dateMatchesByMonthDay($date, $byMonthDay)) {
            return false;
        }

        $byMonth = $rrule->getByMonth();
        if ($rrule->hasByMonth() && $byMonth !== null && !$this->dateMatchesByMonth($date, $byMonth)) {
            return false;
        }

        $byWeekNo = $rrule->getByWeekNo();
        if ($rrule->hasByWeekNo() && $byWeekNo !== null && !$this->dateMatchesByWeekNo($date, $byWeekNo)) {
            return false;
        }

        return true;
    }

    /**
     * Generic BYDAY matching that delegates to frequency-specific method.
     *
     * @param array<array{position: int|null, weekday: string}> $byDay
     */
    private function dateMatchesByDayGeneric(Rrule $rrule, DateTimeImmutable $date, array $byDay): bool
    {
        return match ($rrule->getFrequency()) {
            'DAILY', 'WEEKLY' => $this->dateMatchesWeekdayList($date, $byDay),
            'MONTHLY' => $this->dateMatchesMonthlyByDay($date, $byDay),
            'YEARLY' => $this->dateMatchesYearlyByDay($date, $byDay),
            default => throw new \InvalidArgumentException("Unsupported frequency: {$rrule->getFrequency()}"),
        };
    }

    /**
     * Apply BYSETPOS selection to expanded occurrences.
     *
     * @param array<DateTimeImmutable> $occurrences
     * @param array<int> $bySetPos
     * @return array<DateTimeImmutable>
     */
    private function applyBySetPosSelection(array $occurrences, array $bySetPos): array
    {
        if (empty($occurrences)) {
            return [];
        }

        sort($occurrences);
        $selected = [];
        $count = count($occurrences);

        foreach ($bySetPos as $position) {
            if ($position > 0) {
                // Positive index: 1-based from start
                $index = $position - 1;
                if ($index < $count) {
                    $selected[] = $occurrences[$index];
                }
            } elseif ($position < 0) {
                // Negative index: 1-based from end
                $index = $count + $position;
                if ($index >= 0) {
                    $selected[] = $occurrences[$index];
                }
            }
            // position === 0 is not allowed per RFC 5545 and should be caught by validation
        }

        // Sort selected occurrences and remove duplicates
        sort($selected);

        // Remove duplicates by comparing timestamps
        $uniqueSelected = [];
        $lastTimestamp = null;

        foreach ($selected as $occurrence) {
            $timestamp = $occurrence->getTimestamp();
            if ($timestamp !== $lastTimestamp) {
                $uniqueSelected[] = $occurrence;
                $lastTimestamp = $timestamp;
            }
        }

        $selected = $uniqueSelected;

        return $selected;
    }

    /**
     * Process yearly BYMONTH patterns by treating each month as a separate period for BYSETPOS.
     *
     * @return array<DateTimeImmutable>
     */
    private function processYearlyByMonthPeriods(Rrule $rrule, DateTimeImmutable $yearStart, DateTimeImmutable $start, ?int $maxCount, int $currentCount): array
    {
        $results = [];
        $byMonth = $rrule->getByMonth();

        if ($byMonth === null) {
            return $results;
        }

        $year = (int) $yearStart->format('Y');

        foreach ($byMonth as $monthNumber) {
            // Create month period
            $monthStart = $yearStart->setDate($year, $monthNumber, 1)->setTime(0, 0, 0);
            $monthEnd = $monthStart->modify('last day of this month 23:59:59');

            // Expand occurrences in this month
            $monthOccurrences = [];
            $current = $monthStart;
            $tempRrule = $this->createRruleWithoutBySetPos($rrule);

            while ($current <= $monthEnd) {
                if ($this->dateMatchesRrule($tempRrule, $current)) {
                    $monthOccurrences[] = $current;
                }
                $current = $current->modify('+1 day');
            }

            // Apply BYSETPOS to this month's occurrences
            $selectedOccurrences = $this->applyBySetPosSelection($monthOccurrences, $rrule->getBySetPos() ?? []);

            // Add valid occurrences (>= start date) to results
            foreach ($selectedOccurrences as $occurrence) {
                if ($occurrence >= $start) {
                    $results[] = $occurrence;
                }
            }

            // Early termination check for COUNT
            if ($maxCount !== null && (count($results) + $currentCount) >= $maxCount) {
                break;
            }
        }

        sort($results); // Ensure chronological order

        return $results;
    }

    /**
     * Expand BYWEEKNO occurrences with BYSETPOS, treating each week as a sub-period.
     *
     * @return array<DateTimeImmutable>
     */
    private function expandByWeekNoWithBySetPos(Rrule $rrule, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd): array
    {
        $allOccurrences = [];
        $byWeekNo = $rrule->getByWeekNo();
        $bySetPos = $rrule->getBySetPos();

        if ($byWeekNo === null || $bySetPos === null) {
            return $allOccurrences;
        }

        $year = (int) $periodStart->format('o'); // ISO week year

        foreach ($byWeekNo as $weekNumber) {
            // Skip week 53 if it doesn't exist in this year
            if ($weekNumber === 53 && !DateValidationUtils::yearHasWeek53($year)) {
                continue;
            }

            // Get all dates in this week that match other BY* rules
            $weekOccurrences = [];
            $mondayOfWeek = DateValidationUtils::getFirstDateOfWeek($year, $weekNumber);

            for ($dayOffset = 0; $dayOffset < 7; ++$dayOffset) {
                $weekDate = $mondayOfWeek->modify("+{$dayOffset} days");

                // Only include dates within the period
                if ($weekDate >= $periodStart && $weekDate <= $periodEnd) {
                    // Check if this date matches other BY* rules (like BYDAY if present)
                    $tempRrule = $this->createRruleWithoutBySetPos($rrule);
                    if ($this->dateMatchesRrule($tempRrule, $weekDate)) {
                        $weekOccurrences[] = $weekDate;
                    }
                }
            }

            // Apply BYSETPOS to this week's occurrences
            $selectedFromWeek = $this->applyBySetPosSelection($weekOccurrences, $bySetPos);
            $allOccurrences = array_merge($allOccurrences, $selectedFromWeek);
        }

        return $allOccurrences;
    }

    /**
     * Expand BYMONTH occurrences within a period.
     *
     * @return array<DateTimeImmutable>
     */
    private function expandByMonthInPeriod(Rrule $rrule, DateTimeImmutable $periodStart, DateTimeImmutable $periodEnd): array
    {
        $occurrences = [];
        $byMonth = $rrule->getByMonth();

        if ($byMonth === null) {
            return $occurrences;
        }

        $year = (int) $periodStart->format('Y');

        foreach ($byMonth as $monthNumber) {
            // Create first day of this month in the year
            $monthStart = $periodStart->setDate($year, $monthNumber, 1)->setTime(0, 0, 0);
            $monthEnd = $monthStart->modify('last day of this month 23:59:59');

            // Only process if month overlaps with the period
            if ($monthEnd >= $periodStart && $monthStart <= $periodEnd) {
                // Find all matching dates in this month
                $current = $monthStart;
                while ($current <= $monthEnd) {
                    // Check if this date matches other BY* rules
                    $tempRrule = $this->createRruleWithoutBySetPos($rrule);
                    if ($this->dateMatchesRrule($tempRrule, $current)) {
                        // Only include dates within the period
                        if ($current >= $periodStart && $current <= $periodEnd) {
                            $occurrences[] = $current;
                        }
                    }
                    $current = $current->modify('+1 day');
                }
            }
        }

        return $occurrences;
    }

    /**
     * Get the next period for iteration.
     */
    private function getNextPeriod(Rrule $rrule, DateTimeImmutable $currentPeriod): DateTimeImmutable
    {
        $interval = $rrule->getInterval();

        return match ($rrule->getFrequency()) {
            'YEARLY' => $currentPeriod->modify("+{$interval} years"),
            'MONTHLY' => $currentPeriod->modify("+{$interval} months"),
            'WEEKLY' => $currentPeriod->modify("+{$interval} weeks"),
            'DAILY' => $currentPeriod->modify("+{$interval} days"),
            default => throw new \InvalidArgumentException("Unsupported frequency for BYSETPOS: {$rrule->getFrequency()}"),
        };
    }

    /**
     * Generate occurrences without BYSETPOS (original logic).
     *
     * @return Generator<DateTimeImmutable>
     */
    private function generateOccurrencesWithoutBySetPos(
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
}
