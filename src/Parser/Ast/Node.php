<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

interface Node
{
    public function getName(): string;

    public function getValue(): mixed;

    public function getRawValue(): mixed;
}
