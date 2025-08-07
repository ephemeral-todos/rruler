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
     * @return array<array{position: int|null, weekday: string}>|null
     */
    public function getByDay(): ?array
    {
        return $this->byDay;
    }

    public function hasByDay(): bool
    {
        return $this->byDay !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByMonthDay(): ?array
    {
        return $this->byMonthDay;
    }

    public function hasByMonthDay(): bool
    {
        return $this->byMonthDay !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByMonth(): ?array
    {
        return $this->byMonth;
    }

    public function hasByMonth(): bool
    {
        return $this->byMonth !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByWeekNo(): ?array
    {
        return $this->byWeekNo;
    }

    public function hasByWeekNo(): bool
    {
        return $this->byWeekNo !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getBySetPos(): ?array
    {
        return $this->bySetPos;
    }

    public function hasBySetPos(): bool
    {
        return $this->bySetPos !== null;
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

        return implode(';', $parts);
    }
}
