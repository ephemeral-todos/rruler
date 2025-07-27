<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

final class CountNode implements Node
{
    private readonly int $count;

    public function __construct(private readonly string $rawCount)
    {
        AssertThatNode::isNotEmpty($this);
        AssertThatNode::containsAnInteger($this);

        $trimmedRawCount = trim($rawCount);
        $this->count = (int) $trimmedRawCount;

        AssertThatNode::isNotNegative($this);
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
