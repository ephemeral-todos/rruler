<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidIntegerException;

class AssertThatNode
{
    public static function isNotEmpty(Node $node): void
    {
        $rawValue = $node->getRawValue();

        assert(is_null($rawValue) || is_string($rawValue));

        if (is_null($rawValue) || '' === trim($rawValue)) {
            throw new CannotBeEmptyException($node);
        }
    }

    public static function containsAnInteger(Node $node): void
    {
        self::isNotEmpty($node);

        $rawValue = $node->getRawValue();

        assert(is_string($rawValue));

        if (!is_numeric($rawValue) || str_contains($rawValue, '.')) {
            throw InvalidIntegerException::dueToNotBeingAnInteger($node);
        }
    }

    public static function isNotNegative(Node $node): void
    {
        self::containsAnInteger($node);

        if ($node->getValue() < 0) {
            throw InvalidIntegerException::dueToBeingNegative($node);
        }
    }
}
