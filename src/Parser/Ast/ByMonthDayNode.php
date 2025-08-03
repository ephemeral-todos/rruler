<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

final class ByMonthDayNode implements Node, NodeWithChoices
{
    public const string NAME = 'BYMONTHDAY';

    /**
     * @var array<int>
     */
    private readonly array $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseByMonthDayValue($rawValue);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * @return array<int>
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
     * @return array<string>
     */
    public static function getChoices(): array
    {
        $choices = [];

        // Positive values 1-31
        for ($i = 1; $i <= 31; ++$i) {
            $choices[] = (string) $i;
        }

        // Negative values -1 to -31
        for ($i = -1; $i >= -31; --$i) {
            $choices[] = (string) $i;
        }

        return $choices;
    }

    /**
     * @return array<int>
     */
    private function parseByMonthDayValue(string $value): array
    {
        $daySpecs = explode(',', $value);
        $result = [];

        foreach ($daySpecs as $daySpec) {
            $daySpec = trim($daySpec);

            if ($daySpec === '') {
                throw new ValidationException($this, 'BYMONTHDAY cannot contain empty day specifications');
            }

            $dayValue = $this->parseDaySpec($daySpec);
            $result[] = $dayValue;
        }

        return $result;
    }

    private function parseDaySpec(string $daySpec): int
    {
        // Check if it's a valid integer format
        if (!preg_match('/^-?\d+$/', $daySpec)) {
            throw new ValidationException(
                $this,
                "Invalid day value format: {$daySpec}"
            );
        }

        $dayValue = (int) $daySpec;

        // Validate day value cannot be zero
        if ($dayValue === 0) {
            throw new ValidationException($this, 'Day value cannot be zero');
        }

        // Validate day value range
        if ($dayValue < -31 || $dayValue > 31) {
            throw new ValidationException(
                $this,
                "Day value must be between 1-31 or -1 to -31, got: {$dayValue}"
            );
        }

        return $dayValue;
    }
}
