<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

/**
 * @phpstan-type WeekdayString self::WEEKDAY_*
 * @phpstan-type ByDayValue array{position: int|null, weekday: WeekdayString}
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
