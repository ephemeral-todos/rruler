<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

abstract class Node
{
    abstract public function getValue(): mixed;

    abstract public function validate(): void;
}
