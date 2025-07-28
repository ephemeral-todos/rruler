<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ParseException;

final class RruleAst
{
    /**
     * @var array<string, Node>
     */
    private array $nodes = [];

    public function addNode(string $parameterName, Node $node): void
    {
        $this->nodes[strtoupper($parameterName)] = $node;
    }

    public function hasNode(string $parameterName): bool
    {
        return isset($this->nodes[strtoupper($parameterName)]);
    }

    public function getNode(string $parameterName): Node
    {
        $normalizedName = strtoupper($parameterName);

        if (!isset($this->nodes[$normalizedName])) {
            throw new ParseException("Node {$normalizedName} not found in AST");
        }

        return $this->nodes[$normalizedName];
    }

    /**
     * @return array<string, Node>
     */
    public function getAllNodes(): array
    {
        return $this->nodes;
    }
}
