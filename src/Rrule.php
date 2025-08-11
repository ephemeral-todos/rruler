<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use DateTimeImmutable;
use Stringable;

/**
 * Immutable value object representing a parsed RFC 5545 recurrence rule.
 *
 * The Rrule class encapsulates all parameters of a parsed RRULE string,
 * providing type-safe access to frequency, intervals, constraints, and
 * occurrence selection criteria. All instances are immutable and readonly.
 *
 * This class serves as the primary data structure for working with
 * recurrence patterns throughout the Rruler library, used by occurrence
 * generators and validators to determine recurring event schedules.
 *
 * Key features:
 * - Immutable readonly properties for thread safety
 * - Type-safe access to all RFC 5545 parameters
 * - Structured array format for complex parameters (BYDAY, etc.)
 * - Stringable interface for converting back to RRULE format
 * - Comprehensive validation of parameter combinations
 * - Support for all advanced RFC 5545 features
 *
 * @example Basic usage
 * ```php
 * // Created via Rruler::parse()
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10');
 *
 * echo $rrule->getFrequency(); // 'WEEKLY'
 * echo $rrule->getCount();     // 10
 * var_dump($rrule->getByDay());
 * // [
 * //   ['position' => null, 'weekday' => 'MO'],
 * //   ['position' => null, 'weekday' => 'WE'],
 * //   ['position' => null, 'weekday' => 'FR']
 * // ]
 * ```
 * @example Working with positional BYDAY
 * ```php
 * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=1MO,-1FR');
 *
 * foreach ($rrule->getByDay() as $daySpec) {
 *     $position = $daySpec['position']; // 1 or -1
 *     $weekday = $daySpec['weekday'];   // 'MO' or 'FR'
 *
 *     if ($position > 0) {
 *         echo "First {$weekday} of the month\n";
 *     } else {
 *         echo "Last {$weekday} of the month\n";
 *     }
 * }
 * ```
 * @example Converting back to RRULE string
 * ```php
 * $rruleString = (string) $rrule; // Uses __toString()
 * echo $rruleString; // 'FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10'
 * ```
 * @example Working with date constraints
 * ```php
 * $rrule = $rruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
 *
 * if ($rrule->hasUntil()) {
 *     $until = $rrule->getUntil();
 *     echo $until->format('Y-m-d H:i:s'); // End date
 * }
 *
 * if ($rrule->hasCount()) {
 *     echo "Max occurrences: " . $rrule->getCount();
 * }
 * ```
 *
 * @see Rruler::parse() For creating Rrule instances
 * @see Occurrence\OccurrenceGenerator For generating occurrences
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final readonly class Rrule implements Stringable
{
    /**
     * Creates a new immutable Rrule instance with specified recurrence parameters.
     *
     * This constructor is typically called by {@see Rruler::parse()} rather than
     * directly by user code, as it requires properly validated parameters in the
     * correct format.
     *
     * @param string $frequency RRULE frequency: 'DAILY', 'WEEKLY', 'MONTHLY', or 'YEARLY'
     * @param int $interval Recurrence interval (every N periods). Must be >= 1
     * @param int|null $count Maximum number of occurrences. Mutually exclusive with $until
     * @param DateTimeImmutable|null $until End date for recurrence. Mutually exclusive with $count
     * @param array<array{position: int|null, weekday: string}>|null $byDay Weekday specifications.
     *                                                                      Format: [['position' => 1, 'weekday' => 'MO'], ['position' => null, 'weekday' => 'FR']]
     * @param array<int>|null $byMonthDay Day of month values (1-31, -1 to -31)
     * @param array<int>|null $byMonth Month values (1-12) for yearly patterns
     * @param array<int>|null $byWeekNo Week number values (1-53, -1 to -53) for yearly patterns
     * @param array<int>|null $bySetPos Position values for occurrence selection (1 to N, -1 to -N)
     * @param string|null $weekStart Week start day: 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' (defaults to 'MO')
     *
     * @example Constructor usage (typically internal)
     * ```php
     * // Usually created via: $rruler->parse('FREQ=WEEKLY;BYDAY=MO,FR')
     * $rrule = new Rrule(
     *     frequency: 'WEEKLY',
     *     interval: 1,
     *     count: null,
     *     until: null,
     *     byDay: [
     *         ['position' => null, 'weekday' => 'MO'],
     *         ['position' => null, 'weekday' => 'FR']
     *     ]
     * );
     * ```
     */
    public function __construct(
        private string $frequency,
        private int $interval,
        private ?int $count,
        private ?DateTimeImmutable $until,
        private ?array $byDay = null,
        private ?array $byMonthDay = null,
        private ?array $byMonth = null,
        private ?array $byWeekNo = null,
        private ?array $bySetPos = null,
        private ?string $weekStart = null,
    ) {
    }

    /**
     * Gets the recurrence frequency.
     *
     * @return string One of: 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;INTERVAL=2');
     * echo $rrule->getFrequency(); // 'WEEKLY'
     * ```
     */
    public function getFrequency(): string
    {
        return $this->frequency;
    }

    /**
     * Gets the recurrence interval (every N periods).
     *
     * @return int Interval value (1 = every period, 2 = every other period, etc.)
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;INTERVAL=3');
     * echo $rrule->getInterval(); // 3 (every 3 days)
     * ```
     */
    public function getInterval(): int
    {
        return $this->interval;
    }

    /**
     * Gets the maximum number of occurrences.
     *
     * @return int|null Count value or null if not specified. Mutually exclusive with until date.
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;COUNT=10');
     * echo $rrule->getCount(); // 10
     *
     * $rrule = $rruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
     * var_dump($rrule->getCount()); // null
     * ```
     */
    public function getCount(): ?int
    {
        return $this->count;
    }

    /**
     * Gets the end date for recurrence.
     *
     * @return DateTimeImmutable|null Until date or null if not specified. Mutually exclusive with count.
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
     * echo $rrule->getUntil()->format('Y-m-d'); // '2024-12-31'
     *
     * $rrule = $rruler->parse('FREQ=DAILY;COUNT=10');
     * var_dump($rrule->getUntil()); // null
     * ```
     */
    public function getUntil(): ?DateTimeImmutable
    {
        return $this->until;
    }

    /**
     * Checks if a maximum occurrence count is specified.
     *
     * @return bool True if COUNT parameter is present, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;COUNT=10');
     * if ($rrule->hasCount()) {
     *     echo "Limited to " . $rrule->getCount() . " occurrences";
     * }
     * ```
     */
    public function hasCount(): bool
    {
        return $this->count !== null;
    }

    /**
     * Checks if an end date is specified.
     *
     * @return bool True if UNTIL parameter is present, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;UNTIL=20241231T235959Z');
     * if ($rrule->hasUntil()) {
     *     echo "Ends on " . $rrule->getUntil()->format('Y-m-d');
     * }
     * ```
     */
    public function hasUntil(): bool
    {
        return $this->until !== null;
    }

    /**
     * Gets the weekday specifications for BYDAY parameter.
     *
     * Returns an array of weekday specifications where each element contains
     * a position (for positional references like "first Monday") and weekday code.
     * Position can be null for simple weekday references, positive (1-5) for
     * "Nth weekday", or negative (-1 to -5) for "Nth from end" references.
     *
     * @return array<array{position: int|null, weekday: string}>|null Array of day specifications or null if not set.
     *                                                                Format: [['position' => 1, 'weekday' => 'MO'], ['position' => null, 'weekday' => 'FR']]
     *
     * @example Simple weekdays
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');
     * foreach ($rrule->getByDay() as $daySpec) {
     *     echo $daySpec['weekday']; // 'MO', 'WE', 'FR'
     *     var_dump($daySpec['position']); // null (no position specified)
     * }
     * ```
     * @example Positional weekdays
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=1MO,-1FR');
     * foreach ($rrule->getByDay() as $daySpec) {
     *     if ($daySpec['position'] === 1) {
     *         echo "First {$daySpec['weekday']} of the month"; // "First MO of the month"
     *     } elseif ($daySpec['position'] === -1) {
     *         echo "Last {$daySpec['weekday']} of the month"; // "Last FR of the month"
     *     }
     * }
     * ```
     */
    public function getByDay(): ?array
    {
        return $this->byDay;
    }

    /**
     * Checks if weekday specifications are present.
     *
     * @return bool True if BYDAY parameter is specified, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,FR');
     * if ($rrule->hasByDay()) {
     *     echo "Recurring on specific weekdays";
     *     foreach ($rrule->getByDay() as $daySpec) {
     *         echo $daySpec['weekday'] . " ";
     *     }
     * }
     * ```
     */
    public function hasByDay(): bool
    {
        return $this->byDay !== null;
    }

    /**
     * Gets the day-of-month specifications for BYMONTHDAY parameter.
     *
     * Returns an array of integers representing specific days of the month.
     * Positive values (1-31) count from the beginning of the month, while
     * negative values (-1 to -31) count from the end of the month.
     *
     * @return array<int>|null Array of month day values or null if not set.
     *                         Positive: 1-31 (1st, 2nd, ..., 31st)
     *                         Negative: -1 to -31 (last, second-to-last, etc.)
     *
     * @example Specific days of month
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=1,15,-1');
     * foreach ($rrule->getByMonthDay() as $day) {
     *     if ($day > 0) {
     *         echo "Day {$day} of the month\n"; // "Day 1 of the month", "Day 15 of the month"
     *     } else {
     *         echo "Day {$day} from the end\n"; // "Day -1 from the end" (last day)
     *     }
     * }
     * ```
     * @example Monthly recurring on last day
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=-1');
     * // Will occur on Jan 31, Feb 28/29, Mar 31, Apr 30, etc.
     * echo "Always on the last day of each month";
     * ```
     */
    public function getByMonthDay(): ?array
    {
        return $this->byMonthDay;
    }

    /**
     * Checks if day-of-month specifications are present.
     *
     * @return bool True if BYMONTHDAY parameter is specified, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=15');
     * if ($rrule->hasByMonthDay()) {
     *     echo "Recurring on specific days of the month: ";
     *     echo implode(', ', $rrule->getByMonthDay());
     * }
     * ```
     */
    public function hasByMonthDay(): bool
    {
        return $this->byMonthDay !== null;
    }

    /**
     * Gets the month specifications for BYMONTH parameter.
     *
     * Returns an array of integers representing specific months for yearly
     * patterns. Values range from 1 (January) to 12 (December). This parameter
     * is commonly used with FREQ=YEARLY to create quarterly, semi-annual,
     * or other month-specific recurring patterns.
     *
     * @return array<int>|null Array of month values (1-12) or null if not set.
     *                         1=January, 2=February, ..., 12=December
     *
     * @example Quarterly recurrence
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYMONTHDAY=15');
     * foreach ($rrule->getByMonth() as $month) {
     *     $monthName = date('F', mktime(0, 0, 0, $month, 1));
     *     echo "Recurring in {$monthName}\n"; // "Recurring in March", etc.
     * }
     * // Results in: March 15, June 15, September 15, December 15 each year
     * ```
     * @example Semi-annual pattern
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=1,7;BYDAY=1MO');
     * // First Monday in January and July each year
     * echo "Semi-annual meetings on first Monday";
     * ```
     */
    public function getByMonth(): ?array
    {
        return $this->byMonth;
    }

    /**
     * Checks if month specifications are present.
     *
     * @return bool True if BYMONTH parameter is specified, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=6,12');
     * if ($rrule->hasByMonth()) {
     *     echo "Occurring in specific months: ";
     *     foreach ($rrule->getByMonth() as $month) {
     *         echo date('F', mktime(0, 0, 0, $month, 1)) . " ";
     *     }
     * }
     * ```
     */
    public function hasByMonth(): bool
    {
        return $this->byMonth !== null;
    }

    /**
     * Gets the week number specifications for BYWEEKNO parameter.
     *
     * Returns an array of integers representing specific ISO 8601 week numbers
     * for yearly patterns. Positive values (1-53) count from the beginning of
     * the year, while negative values (-1 to -53) count from the end. This
     * parameter is only valid with FREQ=YEARLY.
     *
     * Week numbers follow ISO 8601 standard where week 1 is the first week
     * that contains at least 4 days in the new year, and weeks start on Monday.
     *
     * @return array<int>|null Array of week numbers (1-53, -1 to -53) or null if not set.
     *                         Positive: 1-53 (1st week, 2nd week, ..., 53rd week)
     *                         Negative: -1 to -53 (last week, second-to-last week, etc.)
     *
     * @example First and last week of year
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYWEEKNO=1,-1;BYDAY=MO');
     * foreach ($rrule->getByWeekNo() as $weekNo) {
     *     if ($weekNo > 0) {
     *         echo "Week {$weekNo} of the year\n";
     *     } else {
     *         echo "Week {$weekNo} from the end\n"; // Week -1 = last week
     *     }
     * }
     * // Results in: First Monday of first week, First Monday of last week
     * ```
     * @example Quarterly week-based pattern
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYWEEKNO=13,26,39,52;BYDAY=FR');
     * // Friday of weeks 13, 26, 39, 52 (roughly quarterly)
     * echo "Quarterly Friday meetings";
     * ```
     */
    public function getByWeekNo(): ?array
    {
        return $this->byWeekNo;
    }

    /**
     * Checks if week number specifications are present.
     *
     * @return bool True if BYWEEKNO parameter is specified, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYWEEKNO=1,52;BYDAY=MO');
     * if ($rrule->hasByWeekNo()) {
     *     echo "Recurring on specific weeks: ";
     *     echo implode(', ', $rrule->getByWeekNo());
     * }
     * ```
     */
    public function hasByWeekNo(): bool
    {
        return $this->byWeekNo !== null;
    }

    /**
     * Gets the position specifications for BYSETPOS parameter.
     *
     * Returns an array of integers representing positions to select from the
     * set of occurrences generated by other BY* rules within each period.
     * Positive values (1-N) select from the beginning, while negative values
     * (-1 to -N) select from the end. This is an advanced parameter that
     * operates on the results of other BY* constraints.
     *
     * BYSETPOS is applied after all other BY* rules are processed, selecting
     * specific positions from the expanded occurrence set within each period
     * (day, week, month, or year depending on FREQ).
     *
     * @return array<int>|null Array of position values (1 to N, -1 to -N) or null if not set.
     *                         Positive: 1-N (1st occurrence, 2nd occurrence, etc.)
     *                         Negative: -1 to -N (last occurrence, second-to-last, etc.)
     *
     * @example Last Friday of each month
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1');
     * // Step 1: Find all Fridays in each month
     * // Step 2: Select the last one (-1) from each month's Fridays
     * echo "Last Friday of each month";
     * ```
     * @example First and third Monday of each month
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1,3');
     * foreach ($rrule->getBySetPos() as $pos) {
     *     echo "Position {$pos} Monday of each month\n";
     * }
     * // Results in: 1st Monday and 3rd Monday of each month
     * ```
     * @example Complex yearly pattern
     * ```php
     * $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=1,4,7,10;BYDAY=MO;BYSETPOS=2');
     * // Find all Mondays in Jan, Apr, Jul, Oct, then select the 2nd Monday from each quarter
     * echo "Second Monday of each quarter";
     * ```
     */
    public function getBySetPos(): ?array
    {
        return $this->bySetPos;
    }

    /**
     * Checks if position specifications are present.
     *
     * @return bool True if BYSETPOS parameter is specified, false otherwise
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1');
     * if ($rrule->hasBySetPos()) {
     *     echo "Using position selection: ";
     *     foreach ($rrule->getBySetPos() as $pos) {
     *         echo ($pos > 0) ? "position {$pos}" : "position {$pos} from end";
     *     }
     * }
     * ```
     */
    public function hasBySetPos(): bool
    {
        return $this->bySetPos !== null;
    }

    /**
     * Gets the week start day for this recurrence rule.
     *
     * Returns the explicitly specified week start day or the RFC 5545 default
     * of 'MO' (Monday) when no WKST parameter is present. This affects how
     * week-based calculations are performed for BYDAY and BYWEEKNO parameters.
     *
     * @return string One of: 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;WKST=SU');
     * echo $rrule->getWeekStart(); // 'SU'
     *
     * $rrule = $rruler->parse('FREQ=WEEKLY');
     * echo $rrule->getWeekStart(); // 'MO' (default)
     * ```
     */
    public function getWeekStart(): string
    {
        return $this->weekStart ?? 'MO';
    }

    /**
     * Checks if a week start day is explicitly specified.
     *
     * Returns true only if the WKST parameter was explicitly set in the
     * original RRULE string, false if using the RFC 5545 default of Monday.
     *
     * @return bool True if WKST parameter is specified, false if using default
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;WKST=SU');
     * echo $rrule->hasWeekStart(); // true
     *
     * $rrule = $rruler->parse('FREQ=WEEKLY');
     * echo $rrule->hasWeekStart(); // false (using default MO)
     * ```
     */
    public function hasWeekStart(): bool
    {
        return $this->weekStart !== null;
    }

    /**
     * Converts the Rrule to a structured array representation.
     *
     * Returns all recurrence parameters as an associative array with standardized
     * keys. Useful for serialization, debugging, or integration with other systems.
     *
     * @return array{freq: string, interval: int, count: int|null, until: DateTimeImmutable|null, byDay: array<array{position: int|null, weekday: string}>|null, byMonthDay: array<int>|null, byMonth: array<int>|null, byWeekNo: array<int>|null, bySetPos: array<int>|null}
     *
     * @example
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,FR;COUNT=10');
     * $array = $rrule->toArray();
     *
     * // Result:
     * // [
     * //     'freq' => 'WEEKLY',
     * //     'interval' => 1,
     * //     'count' => 10,
     * //     'until' => null,
     * //     'byDay' => [
     * //         ['position' => null, 'weekday' => 'MO'],
     * //         ['position' => null, 'weekday' => 'FR']
     * //     ],
     * //     'byMonthDay' => null,
     * //     'byMonth' => null,
     * //     'byWeekNo' => null,
     * //     'bySetPos' => null
     * // ]
     * ```
     */
    public function toArray(): array
    {
        return [
            'freq' => $this->frequency,
            'interval' => $this->interval,
            'count' => $this->count,
            'until' => $this->until,
            'byDay' => $this->byDay,
            'byMonthDay' => $this->byMonthDay,
            'byMonth' => $this->byMonth,
            'byWeekNo' => $this->byWeekNo,
            'bySetPos' => $this->bySetPos,
            'weekStart' => $this->weekStart,
        ];
    }

    /**
     * Converts the Rrule back to RFC 5545 RRULE string format.
     *
     * Reconstructs a valid RRULE string from the parsed parameters, suitable
     * for use in iCalendar files or other RFC 5545 compliant systems. Only
     * includes parameters that have non-default values.
     *
     * UNTIL dates are automatically converted to UTC format as required by
     * RFC 5545 specification.
     *
     * @return string Valid RFC 5545 RRULE string (without "RRULE:" prefix)
     *
     * @example Basic reconstruction
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;INTERVAL=2;COUNT=10');
     * echo (string) $rrule; // 'FREQ=DAILY;INTERVAL=2;COUNT=10'
     * ```
     * @example Complex pattern reconstruction
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=1MO,-1FR;BYMONTH=3,6,9,12');
     * echo (string) $rrule; // 'FREQ=MONTHLY;BYDAY=1MO,-1FR;BYMONTH=3,6,9,12'
     * ```
     * @example UNTIL date formatting
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;UNTIL=20241231T120000Z');
     * echo (string) $rrule; // 'FREQ=DAILY;UNTIL=20241231T120000Z'
     * ```
     */
    public function __toString(): string
    {
        $parts = [];

        $parts[] = "FREQ={$this->frequency}";

        if ($this->interval !== 1) {
            $parts[] = "INTERVAL={$this->interval}";
        }

        if ($this->count !== null) {
            $parts[] = "COUNT={$this->count}";
        }

        if ($this->until !== null) {
            $utcUntil = $this->until->setTimezone(new \DateTimeZone('UTC'));
            $parts[] = 'UNTIL='.$utcUntil->format('Ymd\THis\Z');
        }

        if ($this->byDay !== null) {
            $byDayStrings = [];
            foreach ($this->byDay as $daySpec) {
                $byDayStrings[] = ($daySpec['position'] !== null ? $daySpec['position'] : '').$daySpec['weekday'];
            }
            $parts[] = 'BYDAY='.implode(',', $byDayStrings);
        }

        if ($this->byMonthDay !== null) {
            $parts[] = 'BYMONTHDAY='.implode(',', $this->byMonthDay);
        }

        if ($this->byMonth !== null) {
            $parts[] = 'BYMONTH='.implode(',', $this->byMonth);
        }

        if ($this->byWeekNo !== null) {
            $parts[] = 'BYWEEKNO='.implode(',', $this->byWeekNo);
        }

        if ($this->bySetPos !== null) {
            $parts[] = 'BYSETPOS='.implode(',', $this->bySetPos);
        }

        if ($this->weekStart !== null) {
            $parts[] = "WKST={$this->weekStart}";
        }

        return implode(';', $parts);
    }
}
