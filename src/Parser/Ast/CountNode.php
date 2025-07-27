<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidIntegerException;

final class CountNode implements Node
{
    private readonly int $count;

    public function __construct(private readonly string $rawCount)
    {
        $trimmedRawCount = trim($rawCount);
        $this->count = (int) $trimmedRawCount;

        if ($trimmedRawCount === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!is_numeric($trimmedRawCount) || str_contains($trimmedRawCount, '.')) {
            throw new InvalidIntegerException($this, $trimmedRawCount);
        }

        if ($this->count <= 0) {
            throw new InvalidIntegerException($this, $trimmedRawCount, true);
        }
    }

    public function getValue(): int
    {
        return $this->count;
    }

    public function getRawValue(): string
    {
        return $this->rawCount;
    }
}
