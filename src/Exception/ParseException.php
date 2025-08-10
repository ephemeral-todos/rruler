<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use Exception;

/**
 * Exception thrown when RRULE parsing fails.
 *
 * ParseException is thrown when the Rruler parser cannot successfully parse
 * an RRULE string due to syntax errors, malformed tokens, or structural
 * issues that prevent the creation of a valid Abstract Syntax Tree (AST).
 *
 * This exception indicates fundamental parsing problems that occur before
 * semantic validation. It represents cases where the input cannot be
 * tokenized or structured according to RRULE grammar rules.
 *
 * Common parsing scenarios that trigger this exception:
 * - Malformed RRULE strings with invalid syntax
 * - Incomplete or truncated RRULE data
 * - Invalid parameter-value separators or delimiters
 * - Unexpected characters or encoding issues
 * - Empty or null input strings
 * - Circular references in parser state (safety mechanism)
 *
 * Note: ParseException extends Exception directly rather than RrulerException
 * because parsing failures occur before AST nodes can be created, so there's
 * no node context to associate with the error.
 *
 * @example Malformed RRULE syntax
 * ```php
 * try {
 *     $rruler = new Rruler();
 *     $rrule = $rruler->parse('FREQ=DAILY;;INTERVAL='); // Double semicolon, empty value
 * } catch (ParseException $e) {
 *     echo "Parse error: " . $e->getMessage();
 *     // "Unexpected token at position 12: empty parameter value"
 * }
 * ```
 * @example Invalid parameter format
 * ```php
 * try {
 *     $rrule = $rruler->parse('FREQ DAILY;COUNT=10'); // Missing '=' separator
 * } catch (ParseException $e) {
 *     echo "Syntax error: " . $e->getMessage();
 *     // "Invalid parameter format: expected '=' separator"
 * }
 * ```
 * @example Empty input handling
 * ```php
 * try {
 *     $rrule = $rruler->parse(''); // Empty string
 * } catch (ParseException $e) {
 *     echo "Input error: " . $e->getMessage();
 *     // "RRULE string cannot be empty"
 * }
 * ```
 * @example Handling parsing vs validation errors
 * ```php
 * try {
 *     $rrule = $rruler->parse($userInput);
 * } catch (ParseException $e) {
 *     // Fundamental syntax error - input is unparseable
 *     echo "Cannot parse RRULE: " . $e->getMessage();
 *     // Suggest checking RRULE syntax
 * } catch (ValidationException $e) {
 *     // Parsed successfully but invalid parameters
 *     echo "Invalid RRULE parameters: " . $e->getMessage();
 *     // Suggest checking parameter values
 * }
 * ```
 *
 * @see ValidationException For semantic validation errors
 * @see RrulerException For exceptions with AST node context
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class ParseException extends Exception
{
}
