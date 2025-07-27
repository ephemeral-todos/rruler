<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

final class IntervalNode implements Node
{
    private readonly int $interval;

    public function __construct(private readonly string $rawInterval)
    {
        AssertThatNode::isNotEmpty($this);
        AssertThatNode::containsAnInteger($this);

        $trimmedRawInterval = trim($rawInterval);
        $this->interval = (int) $trimmedRawInterval;

        AssertThatNode::isNotNegative($this);
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
