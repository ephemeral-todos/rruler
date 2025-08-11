<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidChoiceException;

/**
 * AST node representing the WKST parameter of an RRULE.
 *
 * The WkstNode handles parsing and validation of the optional WKST parameter,
 * which specifies the day of the week that is considered the start of a week.
 * This is crucial for proper week-based recurrence calculations in different
 * cultural and business contexts.
 *
 * RFC 5545 defines seven weekday values:
 * - SU: Sunday
 * - MO: Monday (default when WKST is not specified)
 * - TU: Tuesday
 * - WE: Wednesday
 * - TH: Thursday
 * - FR: Friday
 * - SA: Saturday
 *
 * This node implements {@see NodeWithChoices} to provide access to valid
 * weekday values for validation and tooling purposes.
 *
 * Key features:
 * - Validates weekday against RFC 5545 allowed values
 * - Provides type-safe access to weekday constants
 * - Implements choice validation interface
 * - Throws descriptive validation errors
 * - Immutable value object pattern
 * - Supports week calculation integration
 *
 * @example Basic usage
 * ```php
 * $node = new WkstNode('TU');
 * echo $node->getName();     // 'WKST'
 * echo $node->getValue();    // 'TU'
 * echo $node->getRawValue(); // 'TU'
 * ```
 * @example Validation errors
 * ```php
 * try {
 *     $node = new WkstNode('INVALID');
 * } catch (InvalidChoiceException $e) {
 *     echo $e->getMessage(); // Error with valid choices
 * }
 *
 * try {
 *     $node = new WkstNode('');
 * } catch (CannotBeEmptyException $e) {
 *     echo $e->getMessage(); // WKST cannot be empty
 * }
 * ```
 * @example Working with weekday constants
 * ```php
 * $node = new WkstNode(WkstNode::WEEKDAY_TUESDAY);
 * echo $node->getValue(); // 'TU'
 *
 * $validWeekdays = WkstNode::getChoices();
 * var_dump($validWeekdays); // ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA']
 * ```
 *
 * @phpstan-type WeekdayString self::WEEKDAY_*
 *
 * @see Node For the base interface
 * @see NodeWithChoices For choice validation interface
 * @see RruleParser For usage in parsing
 * @see https://tools.ietf.org/html/rfc5545#section-3.2.12 RFC 5545 WKST specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class WkstNode implements Node, NodeWithChoices
{
    public const string NAME = 'WKST';

    public const string WEEKDAY_SUNDAY = 'SU';
    public const string WEEKDAY_MONDAY = 'MO';
    public const string WEEKDAY_TUESDAY = 'TU';
    public const string WEEKDAY_WEDNESDAY = 'WE';
    public const string WEEKDAY_THURSDAY = 'TH';
    public const string WEEKDAY_FRIDAY = 'FR';
    public const string WEEKDAY_SATURDAY = 'SA';

    private const VALID_WEEKDAYS = [
        self::WEEKDAY_SUNDAY,
        self::WEEKDAY_MONDAY,
        self::WEEKDAY_TUESDAY,
        self::WEEKDAY_WEDNESDAY,
        self::WEEKDAY_THURSDAY,
        self::WEEKDAY_FRIDAY,
        self::WEEKDAY_SATURDAY,
    ];

    private readonly string $value;

    /**
     * Creates a new WkstNode with validation.
     *
     * Parses and validates the week start day value against RFC 5545 requirements.
     * The weekday must be one of the seven valid two-letter values and cannot be empty.
     *
     * @param string $rawValue Raw WKST parameter value from RRULE string
     *
     * @throws CannotBeEmptyException When weekday value is empty string
     * @throws InvalidChoiceException When weekday is not a valid RFC 5545 value
     *
     * @example Valid weekdays
     * ```php
     * $sunday = new WkstNode('SU');
     * $monday = new WkstNode('MO');
     * $tuesday = new WkstNode('TU');
     * $wednesday = new WkstNode('WE');
     * $thursday = new WkstNode('TH');
     * $friday = new WkstNode('FR');
     * $saturday = new WkstNode('SA');
     * ```
     */
    public function __construct(private readonly string $rawValue)
    {
        $this->value = $rawValue;

        if ($this->value === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!in_array($this->value, self::VALID_WEEKDAYS, true)) {
            throw new InvalidChoiceException($this);
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Gets the validated week start day value.
     *
     * @return string One of: 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA'
     *
     * @example
     * ```php
     * $node = new WkstNode('TU');
     * echo $node->getValue(); // 'TU'
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
     * Gets all valid weekday choices for validation.
     *
     * Returns the complete list of RFC 5545 compliant weekday values.
     * Used by validation systems and development tools.
     *
     * @return array<string> Valid weekday values: ['SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA']
     *
     * @example
     * ```php
     * $choices = WkstNode::getChoices();
     * foreach ($choices as $weekday) {
     *     echo "Valid weekday: {$weekday}\n";
     * }
     * ```
     */
    public static function getChoices(): array
    {
        return self::VALID_WEEKDAYS;
    }
}
