<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;

final class InvalidIntegerException extends ValidationException
{
    public function __construct(
        Node $node,
        string $invalidValue,
        bool $mustBePositive = false,
    ) {
        $prettyType = NodeTypeUtils::toPrettyName($node);

        if ($mustBePositive && is_numeric($invalidValue) && (int) $invalidValue <= 0) {
            $message = sprintf(
                '%s must be a positive integer, got: %s',
                $prettyType,
                $invalidValue
            );
        } else {
            $message = sprintf(
                '%s must be a valid integer, got: %s',
                $prettyType,
                $invalidValue
            );
        }

        parent::__construct($node, $message);
    }
}
