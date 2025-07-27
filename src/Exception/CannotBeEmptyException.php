<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;

final class CannotBeEmptyException extends ValidationException
{
    public function __construct(string|object $type)
    {
        parent::__construct(sprintf(
            '%s cannot be empty',
            NodeTypeUtils::toPrettyName($type)
        ));
    }
}
