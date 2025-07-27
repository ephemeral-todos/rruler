<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;

final class CannotBeEmptyException extends ValidationException
{
    public function __construct(Node $node)
    {
        parent::__construct($node, sprintf(
            '%s cannot be empty',
            NodeTypeUtils::toPrettyName($node)
        ));
    }
}
