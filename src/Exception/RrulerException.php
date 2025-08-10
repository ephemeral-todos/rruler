<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use Exception;
use Throwable;

/**
 * Base exception class for all Rruler-specific exceptions.
 *
 * RrulerException serves as the parent class for all exceptions thrown by the
 * Rruler library. It provides additional context by associating exceptions with
 * the specific AST node that caused the error, enabling better debugging and
 * error reporting.
 *
 * This design allows consumers to catch all Rruler exceptions with a single
 * catch block while still providing detailed context about parsing failures,
 * validation errors, and other library-specific issues.
 *
 * @example Catching all Rruler exceptions
 * ```php
 * try {
 *     $rruler = new Rruler();
 *     $rrule = $rruler->parse('FREQ=INVALID;COUNT=abc');
 * } catch (RrulerException $e) {
 *     echo "Rruler error: " . $e->getMessage();
 *     $node = $e->getNode();
 *     echo "Problem with: " . get_class($node);
 * }
 * ```
 * @example Specific exception handling
 * ```php
 * try {
 *     $rrule = $rruler->parse('FREQ=DAILY;COUNT=-5');
 * } catch (ValidationException $e) {
 *     echo "Validation failed: " . $e->getMessage();
 * } catch (ParseException $e) {
 *     echo "Parse failed: " . $e->getMessage();
 * } catch (RrulerException $e) {
 *     echo "Other Rruler error: " . $e->getMessage();
 * }
 * ```
 *
 * @see ValidationException For RRULE validation errors
 * @see ParseException For parsing-specific errors
 * @see Node For AST node context information
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
abstract class RrulerException extends Exception
{
    /**
     * Creates a new RrulerException with AST node context.
     *
     * @param Node $node The AST node that caused this exception
     * @param string $message Human-readable error message
     * @param int $code Optional error code for categorization
     * @param Throwable|null $previous Previous exception for chaining
     *
     * @example Creating a custom exception
     * ```php
     * // This is typically done internally by the library
     * $frequencyNode = new FrequencyNode('INVALID');
     * throw new ValidationException(
     *     $frequencyNode,
     *     'Invalid frequency value: INVALID'
     * );
     * ```
     */
    public function __construct(
        private Node $node,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Gets the AST node that caused this exception.
     *
     * Provides access to the specific parser node where the error occurred,
     * enabling detailed debugging and context-aware error handling.
     *
     * @return Node The AST node associated with this exception
     *
     * @example Examining the problematic node
     * ```php
     * try {
     *     $rrule = $rruler->parse('FREQ=DAILY;COUNT=invalid');
     * } catch (RrulerException $e) {
     *     $node = $e->getNode();
     *     if ($node instanceof CountNode) {
     *         echo "Problem with COUNT parameter: " . $node->getRawValue();
     *     }
     * }
     * ```
     */
    public function getNode(): Node
    {
        return $this->node;
    }
}
