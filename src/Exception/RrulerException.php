<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Exception;

use EphemeralTodos\Rruler\Parser\Ast\Node;
use Exception;
use Throwable;

abstract class RrulerException extends Exception
{
    public function __construct(
        private Node $node,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getNode(): Node
    {
        return $this->node;
    }
}
