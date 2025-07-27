<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidIntegerException;

final class IntervalNode implements Node
{
    private readonly int $interval;

    public function __construct(private readonly string $rawInterval)
    {
        $trimmedRawInterval = trim($rawInterval);
        $this->interval = (int) $trimmedRawInterval;

        if ($trimmedRawInterval === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!is_numeric($trimmedRawInterval) || str_contains($trimmedRawInterval, '.')) {
            throw new InvalidIntegerException($this, $trimmedRawInterval);
        }

        if ($this->interval <= 0) {
            throw new InvalidIntegerException($this, $trimmedRawInterval, true);
        }
    }

    public function getValue(): int
    {
        return $this->interval;
    }

    public function getRawValue(): string
    {
        return $this->rawInterval;
    }
}
