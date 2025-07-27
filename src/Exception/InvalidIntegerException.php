<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;

final class InvalidIntegerException extends ValidationException
{
    private function __construct(
        Node $node,
        string $messageTemplate = '%s must be a positive integer, got: %s',
    ) {
        $prettyType = NodeTypeUtils::toPrettyName($node);

        $rawValue = $node->getRawValue();

        $rawValue = match (true) {
            is_scalar($rawValue) => (string) $rawValue,
            default => json_encode($rawValue),
        };

        $message = sprintf($messageTemplate, $prettyType, $rawValue);

        parent::__construct($node, $message);
    }

    public static function dueToNotBeingAnInteger(Node $node): self
    {
        return new self(
            $node,
            '%s must be a valid integer, got: %s',
        );
    }

    public static function dueToBeingNegative(Node $node): self
    {
        return new self(
            $node,
            '%s must be a non-negative integer, got: %s',
        );
    }
}
