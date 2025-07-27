<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;
use EphemeralTodos\Rruler\Parser\Ast\NodeWithChoices;

final class InvalidChoiceException extends ValidationException
{
    public function __construct(Node&NodeWithChoices $node)
    {
        $prettyType = NodeTypeUtils::toPrettyName($node);
        $choicesList = implode(', ', $node->getChoices());
        $value = $node->getValue();

        assert(is_string($value), 'NodeWithChoices implementations must return string values');

        parent::__construct(sprintf(
            'Invalid %s value: %s. Valid values are: %s',
            strtolower($prettyType),
            $value,
            $choicesList
        ));
    }
}
