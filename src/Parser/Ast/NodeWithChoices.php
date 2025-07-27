<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

interface NodeWithChoices
{
    /**
     * @return array<string>
     */
    public static function getChoices(): array;
}
