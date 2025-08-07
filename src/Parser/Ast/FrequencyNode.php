<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidChoiceException;

/**
 * AST node representing the FREQ parameter of an RRULE.
 *
 * The FrequencyNode handles parsing and validation of the required FREQ parameter,
 * which specifies the base recurrence frequency. This is the only required parameter
 * in an RRULE and determines how the recurrence pattern repeats.
 *
 * RFC 5545 defines four frequency values:
 * - DAILY: Recurs every day
 * - WEEKLY: Recurs every week
 * - MONTHLY: Recurs every month
 * - YEARLY: Recurs every year
 *
 * This node implements {@see NodeWithChoices} to provide access to valid
 * frequency values for validation and tooling purposes.
 *
 * Key features:
 * - Validates frequency against RFC 5545 allowed values
 * - Provides type-safe access to frequency constants
 * - Implements choice validation interface
 * - Throws descriptive validation errors
 * - Immutable value object pattern
 *
 * @example Basic usage
 * ```php
 * $node = new FrequencyNode('WEEKLY');
 * echo $node->getName();     // 'FREQ'
 * echo $node->getValue();    // 'WEEKLY'
 * echo $node->getRawValue(); // 'WEEKLY'
 * ```
 * @example Validation errors
 * ```php
 * try {
 *     $node = new FrequencyNode('INVALID');
 * } catch (InvalidChoiceException $e) {
 *     echo $e->getMessage(); // Error with valid choices
 * }
 *
 * try {
 *     $node = new FrequencyNode('');
 * } catch (CannotBeEmptyException $e) {
 *     echo $e->getMessage(); // FREQ cannot be empty
 * }
 * ```
 * @example Working with frequency constants
 * ```php
 * $node = new FrequencyNode(FrequencyNode::FREQUENCY_DAILY);
 * echo $node->getValue(); // 'DAILY'
 *
 * $validFreqs = FrequencyNode::getChoices();
 * var_dump($validFreqs); // ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']
 * ```
 *
 * @phpstan-type FrequencyString self::FREQUENCY_*
 *
 * @see Node For the base interface
 * @see NodeWithChoices For choice validation interface
 * @see RruleParser For usage in parsing
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 FREQ specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class FrequencyNode implements Node, NodeWithChoices
{
    public const string NAME = 'FREQ';

    public const string FREQUENCY_DAILY = 'DAILY';
    public const string FREQUENCY_WEEKLY = 'WEEKLY';
    public const string FREQUENCY_MONTHLY = 'MONTHLY';
    public const string FREQUENCY_YEARLY = 'YEARLY';

    private const VALID_FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_YEARLY,
    ];

    private readonly string $value;

    /**
     * Creates a new FrequencyNode with validation.
     *
     * Parses and validates the frequency value against RFC 5545 requirements.
     * The frequency must be one of the four valid values and cannot be empty.
     *
     * @param string $rawValue Raw FREQ parameter value from RRULE string
     *
     * @throws CannotBeEmptyException When frequency value is empty string
     * @throws InvalidChoiceException When frequency is not a valid RFC 5545 value
     *
     * @example Valid frequencies
     * ```php
     * $daily = new FrequencyNode('DAILY');
     * $weekly = new FrequencyNode('WEEKLY');
     * $monthly = new FrequencyNode('MONTHLY');
     * $yearly = new FrequencyNode('YEARLY');
     * ```
     */
    public function __construct(private readonly string $rawValue)
    {
        $this->value = $rawValue;

        if ($this->value === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!in_array($this->value, self::VALID_FREQUENCIES, true)) {
            throw new InvalidChoiceException($this);
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Gets the validated frequency value.
     *
     * @return string One of: 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'
     *
     * @example
     * ```php
     * $node = new FrequencyNode('WEEKLY');
     * echo $node->getValue(); // 'WEEKLY'
     * ```
     */
    public function getValue(): string
    {
        return $this->value;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    /**
     * Gets all valid frequency choices for validation.
     *
     * Returns the complete list of RFC 5545 compliant frequency values.
     * Used by validation systems and development tools.
     *
     * @return array<string> Valid frequency values: ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY']
     *
     * @example
     * ```php
     * $choices = FrequencyNode::getChoices();
     * foreach ($choices as $freq) {
     *     echo "Valid frequency: {$freq}\n";
     * }
     * ```
     */
    public static function getChoices(): array
    {
        return self::VALID_FREQUENCIES;
    }
}
