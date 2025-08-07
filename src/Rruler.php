<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use EphemeralTodos\Rruler\Parser\Ast\ByDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthNode;
use EphemeralTodos\Rruler\Parser\Ast\BySetPosNode;
use EphemeralTodos\Rruler\Parser\Ast\ByWeekNoNode;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use EphemeralTodos\Rruler\Parser\RruleParser;

/**
 * Main entry point for parsing RFC 5545 recurrence rule strings.
 *
 * Rruler is a standalone RFC 5545 Recurrence Rule (RRULE) parser that converts
 * RRULE strings into structured {@see Rrule} objects. It provides comprehensive
 * support for complex recurring patterns with strict validation and error handling.
 *
 * The parser supports all core RFC 5545 parameters including:
 * - FREQ (DAILY, WEEKLY, MONTHLY, YEARLY) - required
 * - INTERVAL - recurrence interval
 * - COUNT - maximum number of occurrences
 * - UNTIL - end date for recurrence
 * - BYDAY - weekday specifications with optional positional prefixes
 * - BYMONTHDAY - days of month selection
 * - BYMONTH - month selection for yearly patterns
 * - BYWEEKNO - week number selection
 * - BYSETPOS - position-based occurrence selection
 *
 * @example Basic daily recurrence
 * ```php
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=DAILY;INTERVAL=2;COUNT=10');
 *
 * echo $rrule->getFrequency(); // 'DAILY'
 * echo $rrule->getInterval();  // 2
 * echo $rrule->getCount();     // 10
 * ```
 * @example Weekly recurrence with specific days
 * ```php
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');
 *
 * foreach ($rrule->getByDay() as $daySpec) {
 *     echo $daySpec['weekday']; // 'MO', 'WE', 'FR'
 * }
 * ```
 * @example Monthly recurrence with position-based day selection
 * ```php
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=1MO,-1FR');
 *
 * // First Monday and last Friday of each month
 * foreach ($rrule->getByDay() as $daySpec) {
 *     echo $daySpec['position'] . $daySpec['weekday']; // '1MO', '-1FR'
 * }
 * ```
 * @example Complex yearly pattern with multiple constraints
 * ```php
 * $rruler = new Rruler();
 * $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYMONTHDAY=15;COUNT=20');
 *
 * // 15th day of March, June, September, December, max 20 occurrences
 * echo count($rrule->getByMonth());    // 4 (quarterly)
 * echo $rrule->getByMonthDay()[0];     // 15
 * ```
 *
 * @see Rrule For the parsed recurrence rule object
 * @see RruleParser For the underlying parser implementation
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class Rruler
{
    /**
     * Creates a new Rruler instance with optional custom parser.
     *
     * @param RruleParser $parser Optional parser instance. Defaults to new RruleParser()
     *                            for dependency injection and testing purposes.
     *
     * @example Basic usage
     * ```php
     * $rruler = new Rruler();
     * ```
     * @example With custom parser
     * ```php
     * $customParser = new RruleParser(new CustomTokenizer());
     * $rruler = new Rruler($customParser);
     * ```
     */
    public function __construct(private RruleParser $parser = new RruleParser())
    {
    }

    /**
     * Parses an RFC 5545 recurrence rule string into a structured Rrule object.
     *
     * Accepts a valid RRULE string and returns an immutable {@see Rrule} object
     * containing all parsed parameters. The parser performs comprehensive validation
     * including required parameters, mutually exclusive constraints, and parameter
     * value validation.
     *
     * @param string $rruleString Valid RFC 5545 RRULE string (without "RRULE:" prefix)
     * @return Rrule Immutable recurrence rule object containing parsed parameters
     *
     * @throws Exception\ValidationException When RRULE string is invalid
     * @throws Exception\ParseException When parsing fails
     *
     * @example Parse daily recurrence
     * ```php
     * $rruler = new Rruler();
     * $rrule = $rruler->parse('FREQ=DAILY;INTERVAL=2');
     * ```
     * @example Parse weekly with specific days
     * ```php
     * $rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10');
     * ```
     * @example Parse monthly with until date
     * ```php
     * $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=15;UNTIL=20241231T235959Z');
     * ```
     *
     * @see Rrule For the returned object structure
     * @see RruleParser::parse() For detailed parsing implementation
     */
    public function parse(string $rruleString): Rrule
    {
        $ast = $this->parser->parse($rruleString);

        $frequencyNode = $ast->getNode(FrequencyNode::class);
        $frequency = $frequencyNode->getValue();

        $interval = 1; // Default value
        if ($ast->hasNode(IntervalNode::class)) {
            $intervalNode = $ast->getNode(IntervalNode::class);
            $interval = $intervalNode->getValue();
        }

        $count = null;
        if ($ast->hasNode(CountNode::class)) {
            $countNode = $ast->getNode(CountNode::class);
            $count = $countNode->getValue();
        }

        $until = null;
        if ($ast->hasNode(UntilNode::class)) {
            $untilNode = $ast->getNode(UntilNode::class);
            $until = $untilNode->getValue();
        }

        $byDay = null;
        if ($ast->hasNode(ByDayNode::class)) {
            $byDayNode = $ast->getNode(ByDayNode::class);
            $byDay = $byDayNode->getValue();
        }

        $byMonthDay = null;
        if ($ast->hasNode(ByMonthDayNode::class)) {
            $byMonthDayNode = $ast->getNode(ByMonthDayNode::class);
            $byMonthDay = $byMonthDayNode->getValue();
        }

        $byMonth = null;
        if ($ast->hasNode(ByMonthNode::class)) {
            $byMonthNode = $ast->getNode(ByMonthNode::class);
            $byMonth = $byMonthNode->getValue();
        }

        $byWeekNo = null;
        if ($ast->hasNode(ByWeekNoNode::class)) {
            $byWeekNoNode = $ast->getNode(ByWeekNoNode::class);
            $byWeekNo = $byWeekNoNode->getValue();
        }

        $bySetPos = null;
        if ($ast->hasNode(BySetPosNode::class)) {
            $bySetPosNode = $ast->getNode(BySetPosNode::class);
            $bySetPos = $bySetPosNode->getValue();
        }

        return new Rrule($frequency, $interval, $count, $until, $byDay, $byMonthDay, $byMonth, $byWeekNo, $bySetPos);
    }
}
