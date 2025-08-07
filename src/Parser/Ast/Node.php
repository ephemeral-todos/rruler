<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

/**
 * Base interface for all RRULE parameter AST nodes.
 *
 * Each RRULE parameter (FREQ, INTERVAL, COUNT, BYDAY, etc.) is represented
 * by a specific AST node that implements this interface. Nodes are responsible
 * for parsing, validating, and providing structured access to their parameter
 * values.
 *
 * Key responsibilities:
 * - Parse raw string values into structured formats
 * - Validate parameter values according to RFC 5545
 * - Provide both raw and processed value access
 * - Maintain parameter naming consistency
 * - Support detailed error reporting with context
 *
 * The AST-based approach enables:
 * - Better maintainability than regex parsing
 * - Extensibility for new parameters
 * - Precise validation with detailed errors
 * - Type safety for parameter values
 * - Clear separation of parsing concerns
 *
 * @example Working with nodes
 * ```php
 * // Nodes are typically created by RruleParser
 * $parser = new RruleParser();
 * $ast = $parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR');
 *
 * $frequencyNode = $ast->getNode(FrequencyNode::class);
 * echo $frequencyNode->getName();      // 'FREQ'
 * echo $frequencyNode->getValue();     // 'WEEKLY'
 * echo $frequencyNode->getRawValue();  // 'WEEKLY'
 *
 * $byDayNode = $ast->getNode(ByDayNode::class);
 * echo $byDayNode->getName();          // 'BYDAY'
 * var_dump($byDayNode->getValue());    // Structured array
 * echo $byDayNode->getRawValue();      // 'MO,WE,FR'
 * ```
 *
 * @see RruleParser For node creation
 * @see RruleAst For node collection and access
 * @see FrequencyNode Example concrete implementation
 * @see ByDayNode Example complex node implementation
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
interface Node
{
    /**
     * Gets the RRULE parameter name for this node.
     *
     * Returns the RFC 5545 parameter name (e.g., 'FREQ', 'BYDAY', 'COUNT')
     * that this node represents. Used for validation and AST organization.
     *
     * @return string RFC 5545 parameter name (e.g., 'FREQ', 'BYDAY', 'INTERVAL')
     *
     * @example
     * ```php
     * $frequencyNode = new FrequencyNode('WEEKLY');
     * echo $frequencyNode->getName(); // 'FREQ'
     *
     * $byDayNode = new ByDayNode('MO,FR');
     * echo $byDayNode->getName(); // 'BYDAY'
     * ```
     */
    public function getName(): string;

    /**
     * Gets the parsed and validated parameter value.
     *
     * Returns the parameter value in its processed, type-safe format.
     * Simple nodes return strings/integers, while complex nodes return
     * structured arrays. All values are validated according to RFC 5545.
     *
     * @return mixed Processed parameter value:
     *               - string for simple parameters (FREQ, UNTIL)
     *               - int for numeric parameters (INTERVAL, COUNT)
     *               - array for complex parameters (BYDAY, BYMONTHDAY)
     *               - DateTimeImmutable for date parameters (UNTIL)
     *
     * @example
     * ```php
     * $frequencyNode = new FrequencyNode('WEEKLY');
     * echo $frequencyNode->getValue(); // 'WEEKLY' (string)
     *
     * $intervalNode = new IntervalNode('2');
     * echo $intervalNode->getValue(); // 2 (int)
     *
     * $byDayNode = new ByDayNode('1MO,-1FR');
     * var_dump($byDayNode->getValue());
     * // [
     * //   ['position' => 1, 'weekday' => 'MO'],
     * //   ['position' => -1, 'weekday' => 'FR']
     * // ]
     * ```
     */
    public function getValue(): mixed;

    /**
     * Gets the original unparsed parameter value.
     *
     * Returns the raw string value as provided in the RRULE string,
     * before any parsing or validation. Useful for debugging, logging,
     * or reconstructing the original RRULE.
     *
     * @return mixed Original parameter value (typically string)
     *
     * @example
     * ```php
     * $byDayNode = new ByDayNode('1MO,-1FR');
     * echo $byDayNode->getRawValue(); // '1MO,-1FR'
     *
     * $intervalNode = new IntervalNode('2');
     * echo $intervalNode->getRawValue(); // '2' (string, not int)
     * ```
     */
    public function getRawValue(): mixed;
}
