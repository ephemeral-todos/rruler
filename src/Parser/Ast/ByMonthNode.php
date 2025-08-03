<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;

final class ByMonthNode implements Node, NodeWithChoices
{
    public const string NAME = 'BYMONTH';

    /**
     * @var array<int>
     */
    private readonly array $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseByMonthValue($rawValue);
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

        // Months 1-12
        for ($i = 1; $i <= 12; ++$i) {
            $choices[] = (string) $i;
        }

        return $choices;
    }

    /**
     * @return array<int>
     */
    private function parseByMonthValue(string $value): array
    {
        $monthSpecs = explode(',', $value);
        $result = [];

        foreach ($monthSpecs as $monthSpec) {
            $monthSpec = trim($monthSpec);

            if ($monthSpec === '') {
                throw new ValidationException($this, 'BYMONTH cannot contain empty month specifications');
            }

            $monthValue = $this->parseMonthSpec($monthSpec);
            $result[] = $monthValue;
        }

        return $result;
    }

    private function parseMonthSpec(string $monthSpec): int
    {
        // Check if it's a valid integer format (positive or negative)
        if (!preg_match('/^-?\d+$/', $monthSpec)) {
            throw new ValidationException(
                $this,
                "Invalid month value format: {$monthSpec}"
            );
        }

        $monthValue = (int) $monthSpec;

        // Validate month value cannot be zero
        if ($monthValue === 0) {
            throw new ValidationException($this, 'Month value cannot be zero');
        }

        // Validate month value range (1-12)
        if ($monthValue < 1 || $monthValue > 12) {
            throw new ValidationException(
                $this,
                "Month value must be between 1-12, got: {$monthValue}"
            );
        }

        return $monthValue;
    }
}
