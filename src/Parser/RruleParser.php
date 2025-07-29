<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\RruleAst;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;

final class RruleParser
{
    /**
     * @var array<string,class-string<Node>>
     */
    private const array NODE_MAP = [
        FrequencyNode::NAME => FrequencyNode::class,
        IntervalNode::NAME => IntervalNode::class,
        CountNode::NAME => CountNode::class,
        UntilNode::NAME => UntilNode::class,
    ];

    public function __construct(private Tokenizer $tokenizer = new Tokenizer())
    {
    }

    public function parse(string $rruleString): RruleAst
    {
        $tokens = $this->tokenizer->tokenize($rruleString);

        $this->validateRequiredParameters($tokens);
        $this->validateMutuallyExclusiveParameters($tokens);

        $ast = new RruleAst();

        foreach ($tokens as $parameterName => $value) {
            $node = $this->createNode($parameterName, $value);
            $ast->addNode($node);
        }

        return $ast;
    }

    /**
     * @param array<string, string> $tokens
     */
    private function validateRequiredParameters(array $tokens): void
    {
        if (!isset($tokens['FREQ'])) {
            throw new ValidationException(
                new class implements Node {
                    public function getName(): string
                    {
                        throw new \LogicException('Not implemented yet.');
                    }

                    public function getValue(): mixed
                    {
                        return null;
                    }

                    public function getRawValue(): mixed
                    {
                        return null;
                    }
                },
                'FREQ parameter is required'
            );
        }
    }

    /**
     * @param array<string, string> $tokens
     */
    private function validateMutuallyExclusiveParameters(array $tokens): void
    {
        if (isset($tokens['COUNT']) && isset($tokens['UNTIL'])) {
            throw new ValidationException(
                new class implements Node {
                    public function getName(): string
                    {
                        throw new \LogicException('Not implemented yet.');
                    }

                    public function getValue(): mixed
                    {
                        return null;
                    }

                    public function getRawValue(): mixed
                    {
                        return null;
                    }
                },
                'COUNT and UNTIL are mutually exclusive'
            );
        }
    }

    private function createNode(string $parameterName, string $value): Node
    {
        if (!array_key_exists($parameterName, self::NODE_MAP)) {
            throw new ValidationException(
                new class implements Node {
                    public function getName(): string
                    {
                        throw new \LogicException('Not implemented yet.');
                    }

                    public function getValue(): mixed
                    {
                        return null;
                    }

                    public function getRawValue(): mixed
                    {
                        return null;
                    }
                },
                "Unsupported parameter: {$parameterName}"
            );
        }

        $nodeClass = self::NODE_MAP[$parameterName];

        return new $nodeClass($value);
    }
}
