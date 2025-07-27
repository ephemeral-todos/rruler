<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidIntegerException;

final class IntervalNode extends Node
{
    private readonly int $interval;

    public function __construct(string $interval)
    {
        $trimmed = trim($interval);

        if ($trimmed === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!is_numeric($trimmed) || str_contains($trimmed, '.')) {
            throw new InvalidIntegerException($this, $trimmed);
        }

        $this->interval = (int) $trimmed;
    }

    public function getValue(): int
    {
        return $this->interval;
    }

    public function validate(): void
    {
        if ($this->interval <= 0) {
            throw new InvalidIntegerException($this, (string) $this->interval, true);
        }
    }
}
