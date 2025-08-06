<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

final class BySetPosNode implements Node, NodeWithChoices
{
    public const string NAME = 'BYSETPOS';

    /**
     * @var array<int>
     */
    private readonly array $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseBySetPosValue($rawValue);
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

        // Positive positions 1-366 (maximum days in a leap year)
        for ($i = 1; $i <= 366; ++$i) {
            $choices[] = (string) $i;
        }

        // Negative positions -1 to -366
        for ($i = -1; $i >= -366; --$i) {
            $choices[] = (string) $i;
        }

        return $choices;
    }

    /**
     * @return array<int>
     */
    private function parseBySetPosValue(string $value): array
    {
        $positionSpecs = explode(',', $value);
        $result = [];

        foreach ($positionSpecs as $positionSpec) {
            $positionSpec = trim($positionSpec);

            if ($positionSpec === '') {
                throw new ValidationException($this, 'BYSETPOS cannot contain empty position specifications');
            }

            $positionValue = $this->parsePositionSpec($positionSpec);
            $result[] = $positionValue;
        }

        return $result;
    }

    private function parsePositionSpec(string $positionSpec): int
    {
        // Check if it's a valid integer format (positive or negative, no leading +)
        if (!preg_match('/^-?\d+$/', $positionSpec)) {
            throw new ValidationException(
                $this,
                "Invalid position format: {$positionSpec}"
            );
        }

        $positionValue = (int) $positionSpec;

        // Validate position value cannot be zero (per RFC 5545)
        if ($positionValue === 0) {
            throw new ValidationException($this, 'Position value cannot be zero');
        }

        // Validate position value range (reasonable bounds)
        // Using 366 as maximum (leap year days) which covers most practical cases
        if ($positionValue < -366 || $positionValue > 366) {
            throw new ValidationException(
                $this,
                "Position value must be between -366 and 366, got: {$positionValue}"
            );
        }

        return $positionValue;
    }
}
