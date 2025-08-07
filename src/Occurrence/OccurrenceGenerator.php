<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Rrule;
use Generator;

/**
 * Interface for generating occurrence dates from recurrence rules.
 *
 * The OccurrenceGenerator interface defines the contract for classes that
 * can calculate occurrence dates from parsed {@see Rrule} objects. This
 * abstraction allows for different generation strategies and implementations
 * while maintaining consistent behavior.
 *
 * Key responsibilities:
 * - Generate occurrence dates based on RRULE parameters
 * - Handle count and date range limitations
 * - Support complex recurrence patterns (BYDAY, BYMONTHDAY, etc.)
 * - Implement RFC 5545 compliant date calculations
 * - Provide efficient iteration over large occurrence sets
 *
 * The interface uses PHP Generators to enable memory-efficient iteration
 * over potentially infinite recurrence sequences. This allows processing
 * of very long recurring series without loading all dates into memory.
 *
 * @example Basic occurrence generation
 * ```php
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10');
 * $start = new DateTimeImmutable('2024-01-01 09:00:00');
 *
 * $generator = new DefaultOccurrenceGenerator();
 * foreach ($generator->generateOccurrences($rrule, $start, 5) as $occurrence) {
 *     echo $occurrence->format('Y-m-d H:i:s') . "\n";
 * }
 * ```
 * @example Range-based generation
 * ```php
 * $rangeStart = new DateTimeImmutable('2024-06-01');
 * $rangeEnd = new DateTimeImmutable('2024-12-31');
 *
 * foreach ($generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd) as $occurrence) {
 *     echo $occurrence->format('Y-m-d') . "\n";
 * }
 * ```
 *
 * @see DefaultOccurrenceGenerator For the default implementation
 * @see Rrule For the recurrence rule structure
 * @see OccurrenceValidator For occurrence validation
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
interface OccurrenceGenerator
{
    /**
     * Generates occurrence dates from an RRULE pattern starting from a given date.
     *
     * Creates a generator that yields occurrence dates based on the recurrence
     * rule parameters. Handles COUNT and UNTIL termination conditions, complex
     * BYDAY/BYMONTHDAY patterns, and all RFC 5545 frequency types.
     *
     * The generator respects the RRULE's intrinsic limits (COUNT/UNTIL) but
     * allows overriding with a custom limit parameter for testing or pagination.
     *
     * @param Rrule $rrule Parsed recurrence rule containing pattern parameters
     * @param DateTimeImmutable $start Starting date/time for the recurrence pattern
     * @param int|null $limit Optional limit to override RRULE's COUNT parameter
     *                        If null, uses COUNT from RRULE or generates indefinitely
     * @return Generator<DateTimeImmutable> Generator yielding occurrence dates in chronological order
     *
     * @example Basic daily recurrence
     * ```php
     * $rrule = $rruler->parse('FREQ=DAILY;INTERVAL=2;COUNT=5');
     * $start = new DateTimeImmutable('2024-01-01 09:00:00');
     *
     * foreach ($generator->generateOccurrences($rrule, $start) as $occurrence) {
     *     echo $occurrence->format('Y-m-d H:i:s') . "\n";
     * }
     * // Output:
     * // 2024-01-01 09:00:00
     * // 2024-01-03 09:00:00
     * // 2024-01-05 09:00:00
     * // 2024-01-07 09:00:00
     * // 2024-01-09 09:00:00
     * ```
     * @example Complex weekly pattern with limit override
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');
     * $start = new DateTimeImmutable('2024-01-01 14:30:00');
     *
     * foreach ($generator->generateOccurrences($rrule, $start, 3) as $occurrence) {
     *     echo $occurrence->format('Y-m-d l') . "\n";
     * }
     * // Output: First 3 occurrences on Mon/Wed/Fri
     * ```
     * @example UNTIL termination
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=15;UNTIL=20241231T235959Z');
     *
     * foreach ($generator->generateOccurrences($rrule, $start) as $occurrence) {
     *     // Generates 15th of each month until December 31, 2024
     * }
     * ```
     */
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): Generator;

    /**
     * Generates occurrence dates within a specific date range.
     *
     * Filters occurrences from the full recurrence pattern to only include
     * those falling within the specified date range. Efficiently skips
     * occurrences before the range start and stops when range end is exceeded.
     *
     * This method is ideal for calendar displays, reports, or any scenario
     * where you need occurrences for a specific time period.
     *
     * @param Rrule $rrule Parsed recurrence rule containing pattern parameters
     * @param DateTimeImmutable $start Starting date/time for the recurrence pattern
     * @param DateTimeImmutable $rangeStart Earliest date to include in results (inclusive)
     * @param DateTimeImmutable $rangeEnd Latest date to include in results (inclusive)
     * @return Generator<DateTimeImmutable> Generator yielding occurrences within the range
     *
     * @example Monthly occurrences in a quarter
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=1');
     * $start = new DateTimeImmutable('2024-01-01');
     * $rangeStart = new DateTimeImmutable('2024-04-01');
     * $rangeEnd = new DateTimeImmutable('2024-06-30');
     *
     * foreach ($generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd) as $occurrence) {
     *     echo $occurrence->format('Y-m-d') . "\n";
     * }
     * // Output:
     * // 2024-04-01 (1st of April)
     * // 2024-05-01 (1st of May)
     * // 2024-06-01 (1st of June)
     * ```
     * @example Weekly pattern filtered by range
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=TU,TH');
     * $start = new DateTimeImmutable('2024-01-01');
     * $rangeStart = new DateTimeImmutable('2024-06-01');
     * $rangeEnd = new DateTimeImmutable('2024-06-07');
     *
     * // Only returns Tue/Thu occurrences in that specific week
     * foreach ($generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd) as $occurrence) {
     *     echo $occurrence->format('Y-m-d l') . "\n";
     * }
     * ```
     * @example Empty range handling
     * ```php
     * // If no occurrences fall within range, generator yields nothing
     * $rangeStart = new DateTimeImmutable('2024-12-01');
     * $rangeEnd = new DateTimeImmutable('2024-12-02');
     *
     * $count = 0;
     * foreach ($generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd) as $occurrence) {
     *     $count++;
     * }
     * // $count may be 0 if no occurrences fall in range
     * ```
     */
    public function generateOccurrencesInRange(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
    ): Generator;
}
