<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

final class IntervalNode implements Node
{
    public const string NAME = 'INTERVAL';

    private readonly int $value;

    public function __construct(private readonly string $rawValue)
    {
        AssertThatNode::isNotEmpty($this);
        AssertThatNode::containsAnInteger($this);

        $this->value = (int) $rawValue;

        AssertThatNode::isNotNegative($this);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getValue(): int
    {
        return $this->value;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }
}
