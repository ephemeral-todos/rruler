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

    public function addNode(Node $node): void
    {
        $this->nodes[$node->getName()] = $node;
    }

    /**
     * @param string|class-string<Node> $parameterName
     */
    public function hasNode(string $parameterName): bool
    {
        // @todo Revisit this implementation to handle class-string properly
        $normalizedParameterName = class_exists($parameterName) ? $parameterName::NAME : $parameterName;

        return isset($this->nodes[$normalizedParameterName]);
    }

    /**
     * @template T of string|class-string<Node>
     *
     * @param T $parameterName
     * @return (T is class-string<Node> ? new<T> : Node)
     */
    public function getNode(string $parameterName): Node
    {
        /** @var string $normalizedParameterName */
        $normalizedParameterName = class_exists($parameterName) ? $parameterName::NAME : $parameterName;

        if (!isset($this->nodes[$normalizedParameterName])) {
            throw new ParseException("Node {$normalizedParameterName} not found in AST");
        }

        return $this->nodes[$normalizedParameterName];
    }

    /**
     * @return array<string, Node>
     */
    public function getAllNodes(): array
    {
        return $this->nodes;
    }
}
