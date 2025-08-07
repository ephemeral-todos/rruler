<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

/**
 * AST node representing the BYDAY parameter of an RRULE.
 *
 * The ByDayNode handles parsing and validation of the BYDAY parameter, which
 * specifies which days of the week recurrence should occur. This parameter
 * supports both simple weekday specifications (MO, TU, WE) and positional
 * specifications (1MO = first Monday, -1FR = last Friday).
 *
 * BYDAY is one of the most complex RRULE parameters, supporting:
 * - Simple weekday lists: MO,WE,FR (every Monday, Wednesday, Friday)
 * - Positional weekdays: 1MO (first Monday), -1FR (last Friday)
 * - Mixed combinations: 1MO,WE,-1FR (first Monday, every Wednesday, last Friday)
 *
 * Position values:
 * - Positive numbers (1-53): Count from start of period
 * - Negative numbers (-1 to -53): Count from end of period
 * - No position: All instances of that weekday in the period
 * - Position 0 is invalid per RFC 5545
 *
 * The node parses comma-separated day specifications into structured arrays
 * containing position and weekday information for each specification.
 *
 * @example Simple weekday specifications
 * ```php
 * $node = new ByDayNode('MO,WE,FR');
 * $value = $node->getValue();
 * // [
 * //   ['position' => null, 'weekday' => 'MO'],
 * //   ['position' => null, 'weekday' => 'WE'],
 * //   ['position' => null, 'weekday' => 'FR']
 * // ]
 * ```
 * @example Positional weekday specifications
 * ```php
 * $node = new ByDayNode('1MO,-1FR');
 * $value = $node->getValue();
 * // [
 * //   ['position' => 1, 'weekday' => 'MO'],   // First Monday
 * //   ['position' => -1, 'weekday' => 'FR']   // Last Friday
 * // ]
 * ```
 * @example Mixed specifications
 * ```php
 * $node = new ByDayNode('1MO,WE,-1FR');
 * $value = $node->getValue();
 * // [
 * //   ['position' => 1, 'weekday' => 'MO'],   // First Monday
 * //   ['position' => null, 'weekday' => 'WE'], // All Wednesdays
 * //   ['position' => -1, 'weekday' => 'FR']   // Last Friday
 * // ]
 * ```
 * @example Validation errors
 * ```php
 * try {
 *     $node = new ByDayNode('0MO'); // Position 0 is invalid
 * } catch (ValidationException $e) {
 *     echo $e->getMessage(); // Position validation error
 * }
 *
 * try {
 *     $node = new ByDayNode('MONDAY'); // Invalid weekday format
 * } catch (ValidationException $e) {
 *     echo $e->getMessage(); // Format validation error
 * }
 * ```
 *
 * @phpstan-type WeekdayString self::WEEKDAY_*
 * @phpstan-type ByDayValue array{position: int|null, weekday: WeekdayString}
 *
 * @see Node For the base interface
 * @see RruleParser For usage in parsing
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 BYDAY specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class ByDayNode implements Node
{
    public const string NAME = 'BYDAY';

    public const string WEEKDAY_MONDAY = 'MO';
    public const string WEEKDAY_TUESDAY = 'TU';
    public const string WEEKDAY_WEDNESDAY = 'WE';
    public const string WEEKDAY_THURSDAY = 'TH';
    public const string WEEKDAY_FRIDAY = 'FR';
    public const string WEEKDAY_SATURDAY = 'SA';
    public const string WEEKDAY_SUNDAY = 'SU';

    private const VALID_WEEKDAYS = [
        self::WEEKDAY_MONDAY,
        self::WEEKDAY_TUESDAY,
        self::WEEKDAY_WEDNESDAY,
        self::WEEKDAY_THURSDAY,
        self::WEEKDAY_FRIDAY,
        self::WEEKDAY_SATURDAY,
        self::WEEKDAY_SUNDAY,
    ];

    /**
     * @var array<ByDayValue>
     */
    private readonly array $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseByDayValue($rawValue);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<ByDayValue>
     */
    public function getValue(): array
    {
        return $this->value;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    /**
     * @return array<ByDayValue>
     */
    private function parseByDayValue(string $value): array
    {
        $daySpecs = explode(',', $value);
        $result = [];

        foreach ($daySpecs as $daySpec) {
            $daySpec = trim($daySpec);

            if ($daySpec === '') {
                throw new ValidationException($this, 'BYDAY cannot contain empty day specifications');
            }

            $parsed = $this->parseDaySpec($daySpec);
            $result[] = $parsed;
        }

        return $result;
    }

    /**
     * @return ByDayValue
     */
    private function parseDaySpec(string $daySpec): array
    {
        // Check for position prefix (e.g., "1MO", "-2FR", "MO")
        $pattern = '/^([+-]?\d+)?('.implode('|', self::VALID_WEEKDAYS).')$/';
        if (preg_match($pattern, $daySpec, $matches)) {
            $positionStr = $matches[1];
            $weekday = $matches[2];

            $position = null;
            if ($positionStr !== '') {
                $position = (int) $positionStr;

                // RFC 5545: Position must be between -53 and 53, excluding 0
                if ($position === 0 || $position < -53 || $position > 53) {
                    throw new ValidationException(
                        $this,
                        "Invalid position '{$position}' in BYDAY. Position must be between -53 and 53, excluding 0"
                    );
                }
            }

            return [
                'position' => $position,
                'weekday' => $weekday,
            ];
        }

        throw new ValidationException(
            $this,
            "Invalid BYDAY specification '{$daySpec}'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')"
        );
    }
}
