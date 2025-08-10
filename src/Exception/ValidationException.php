<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

/**
 * Exception thrown when RRULE validation fails.
 *
 * ValidationException is thrown when the Rruler parser encounters RRULE
 * strings that are syntactically parseable but violate RFC 5545 validation
 * rules. This includes invalid parameter values, mutually exclusive parameter
 * combinations, required parameter omissions, and constraint violations.
 *
 * Common validation scenarios that trigger this exception:
 * - Missing required FREQ parameter
 * - Invalid frequency values (not DAILY/WEEKLY/MONTHLY/YEARLY)
 * - Negative or zero INTERVAL values
 * - COUNT and UNTIL specified together (mutually exclusive)
 * - Invalid weekday codes in BYDAY parameter
 * - Out-of-range values for BYMONTHDAY, BYMONTH, etc.
 * - BYSETPOS without other BY* parameters
 *
 * This exception indicates that the RRULE string was successfully parsed
 * into tokens but failed semantic validation according to RFC 5545 rules.
 *
 * @example Missing required parameter
 * ```php
 * try {
 *     $rruler = new Rruler();
 *     $rrule = $rruler->parse('INTERVAL=2;COUNT=10'); // Missing FREQ
 * } catch (ValidationException $e) {
 *     echo "Validation error: " . $e->getMessage();
 *     // "FREQ parameter is required"
 * }
 * ```
 * @example Invalid parameter value
 * ```php
 * try {
 *     $rrule = $rruler->parse('FREQ=INVALID;COUNT=10');
 * } catch (ValidationException $e) {
 *     echo "Invalid frequency: " . $e->getMessage();
 *     // "Invalid frequency: INVALID. Must be one of: DAILY, WEEKLY, MONTHLY, YEARLY"
 * }
 * ```
 * @example Mutually exclusive parameters
 * ```php
 * try {
 *     $rrule = $rruler->parse('FREQ=DAILY;COUNT=10;UNTIL=20241231T235959Z');
 * } catch (ValidationException $e) {
 *     echo "Parameter conflict: " . $e->getMessage();
 *     // "COUNT and UNTIL are mutually exclusive"
 * }
 * ```
 * @example Invalid range values
 * ```php
 * try {
 *     $rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=35'); // No month has 35 days
 * } catch (ValidationException $e) {
 *     echo "Invalid day: " . $e->getMessage();
 *     // "BYMONTHDAY value 35 is out of valid range (1-31, -1 to -31)"
 * }
 * ```
 *
 * @see RrulerException For the base exception class
 * @see ParseException For parsing-specific errors
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
class ValidationException extends RrulerException
{
}
