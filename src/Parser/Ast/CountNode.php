<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidIntegerException;

final class CountNode extends Node
{
    private readonly int $count;

    public function __construct(string $count)
    {
        $trimmed = trim($count);

        if ($trimmed === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!is_numeric($trimmed) || str_contains($trimmed, '.')) {
            throw new InvalidIntegerException($this, $trimmed);
        }

        $this->count = (int) $trimmed;
    }

    public function getValue(): int
    {
        return $this->count;
    }

    public function validate(): void
    {
        if ($this->count <= 0) {
            throw new InvalidIntegerException($this, (string) $this->count, true);
        }
    }
}
