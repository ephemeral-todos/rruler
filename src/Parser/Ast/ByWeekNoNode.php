<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

final class ByWeekNoNode implements Node, NodeWithChoices
{
    public const string NAME = 'BYWEEKNO';

    /**
     * @var array<int>
     */
    private readonly array $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseByWeekNoValue($rawValue);
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

        // Week numbers 1-53
        for ($i = 1; $i <= 53; ++$i) {
            $choices[] = (string) $i;
        }

        return $choices;
    }

    /**
     * @return array<int>
     */
    private function parseByWeekNoValue(string $value): array
    {
        $weekSpecs = explode(',', $value);
        $result = [];

        foreach ($weekSpecs as $weekSpec) {
            $weekSpec = trim($weekSpec);

            if ($weekSpec === '') {
                throw new ValidationException($this, 'BYWEEKNO cannot contain empty week specifications');
            }

            $weekValue = $this->parseWeekSpec($weekSpec);
            $result[] = $weekValue;
        }

        return $result;
    }

    private function parseWeekSpec(string $weekSpec): int
    {
        // Check if it's a valid integer format (positive or negative)
        if (!preg_match('/^-?\d+$/', $weekSpec)) {
            throw new ValidationException(
                $this,
                "Invalid week number format: {$weekSpec}"
            );
        }

        $weekValue = (int) $weekSpec;

        // Validate week value cannot be zero
        if ($weekValue === 0) {
            throw new ValidationException($this, 'Week number cannot be zero');
        }

        // Validate week value range (1-53)
        if ($weekValue < 1 || $weekValue > 53) {
            throw new ValidationException(
                $this,
                "Week number must be between 1-53, got: {$weekValue}"
            );
        }

        return $weekValue;
    }
}