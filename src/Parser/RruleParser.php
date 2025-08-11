<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthNode;
use EphemeralTodos\Rruler\Parser\Ast\BySetPosNode;
use EphemeralTodos\Rruler\Parser\Ast\ByWeekNoNode;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\Node;
use EphemeralTodos\Rruler\Parser\Ast\RruleAst;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use EphemeralTodos\Rruler\Parser\Ast\WkstNode;

/**
 * Parses RFC 5545 recurrence rule strings into Abstract Syntax Tree (AST) representation.
 *
 * The RruleParser is the core parsing component that tokenizes RRULE strings and
 * converts them into structured AST nodes. It performs comprehensive validation
 * including parameter requirements, mutual exclusivity constraints, and value
 * format validation.
 *
 * The parser uses an AST-based architecture for better maintainability and
 * extensibility compared to regex-based approaches. Each RRULE parameter is
 * represented by a dedicated AST node class with specific validation logic.
 *
 * Key features:
 * - AST-based parsing for better maintainability
 * - Comprehensive parameter validation
 * - Strict RFC 5545 compliance
 * - Extensible node architecture
 * - Detailed error reporting with context
 * - Support for all advanced RRULE parameters
 *
 * Supported parameters:
 * - FREQ (required): DAILY, WEEKLY, MONTHLY, YEARLY
 * - INTERVAL: Recurrence interval (default: 1)
 * - COUNT: Maximum occurrences (mutually exclusive with UNTIL)
 * - UNTIL: End date (mutually exclusive with COUNT)
 * - BYDAY: Weekday specifications with optional positions
 * - BYMONTHDAY: Day of month selection
 * - BYMONTH: Month selection
 * - BYWEEKNO: Week number selection
 * - BYSETPOS: Position-based occurrence selection
 * - WKST: Week start day configuration
 *
 * @example Basic usage
 * ```php
 * $parser = new RruleParser();
 * $ast = $parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10');
 *
 * $frequencyNode = $ast->getNode(FrequencyNode::class);
 * echo $frequencyNode->getValue(); // 'WEEKLY'
 * ```
 * @example Custom tokenizer
 * ```php
 * $customTokenizer = new CustomTokenizer();
 * $parser = new RruleParser($customTokenizer);
 * $ast = $parser->parse('FREQ=DAILY;INTERVAL=2');
 * ```
 * @example Validation errors
 * ```php
 * try {
 *     $parser->parse('INTERVAL=2'); // Missing required FREQ
 * } catch (ValidationException $e) {
 *     echo $e->getMessage(); // 'FREQ parameter is required'
 * }
 *
 * try {
 *     $parser->parse('FREQ=DAILY;COUNT=10;UNTIL=20241231T235959Z');
 * } catch (ValidationException $e) {
 *     echo $e->getMessage(); // 'COUNT and UNTIL are mutually exclusive'
 * }
 * ```
 *
 * @see RruleAst For the returned AST structure
 * @see Tokenizer For the tokenization process
 * @see Node For individual parameter node implementations
 * @see https://tools.ietf.org/html/rfc5545#section-3.3.10 RFC 5545 RRULE specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
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
        ByDayNode::NAME => ByDayNode::class,
        ByMonthDayNode::NAME => ByMonthDayNode::class,
        ByMonthNode::NAME => ByMonthNode::class,
        BySetPosNode::NAME => BySetPosNode::class,
        ByWeekNoNode::NAME => ByWeekNoNode::class,
        WkstNode::NAME => WkstNode::class,
    ];

    /**
     * Creates a new RruleParser instance with optional custom tokenizer.
     *
     * @param Tokenizer $tokenizer Optional tokenizer instance. Defaults to new Tokenizer()
     *                             for dependency injection and testing purposes.
     *
     * @example Basic usage
     * ```php
     * $parser = new RruleParser();
     * ```
     * @example With custom tokenizer
     * ```php
     * $customTokenizer = new CustomTokenizer();
     * $parser = new RruleParser($customTokenizer);
     * ```
     */
    public function __construct(private Tokenizer $tokenizer = new Tokenizer())
    {
    }

    /**
     * Parses an RFC 5545 recurrence rule string into an Abstract Syntax Tree.
     *
     * Tokenizes the RRULE string and converts each parameter into a specialized
     * AST node. Performs comprehensive validation including required parameters,
     * mutually exclusive constraints, and BYSETPOS requirements.
     *
     * The parsing process:
     * 1. Tokenize the RRULE string into parameter/value pairs
     * 2. Validate required parameters (FREQ)
     * 3. Validate mutually exclusive parameters (COUNT/UNTIL)
     * 4. Validate BYSETPOS requirements
     * 5. Create AST nodes for each parameter
     * 6. Return structured AST representation
     *
     * @param string $rruleString Valid RFC 5545 RRULE string (without "RRULE:" prefix)
     * @return RruleAst Abstract Syntax Tree containing parsed parameter nodes
     *
     * @throws ValidationException When RRULE string violates RFC 5545 rules:
     *                             - Missing required FREQ parameter
     *                             - COUNT and UNTIL specified together
     *                             - BYSETPOS without expandable BY* rules
     *                             - Invalid parameter names
     *                             - Invalid parameter values (handled by node classes)
     *
     * @example Parse basic daily rule
     * ```php
     * $parser = new RruleParser();
     * $ast = $parser->parse('FREQ=DAILY;INTERVAL=2;COUNT=10');
     *
     * $frequency = $ast->getNode(FrequencyNode::class)->getValue(); // 'DAILY'
     * $interval = $ast->getNode(IntervalNode::class)->getValue();   // 2
     * $count = $ast->getNode(CountNode::class)->getValue();         // 10
     * ```
     * @example Parse complex weekly rule
     * ```php
     * $ast = $parser->parse('FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1,-1');
     *
     * $byDayNode = $ast->getNode(ByDayNode::class);
     * $bySetPosNode = $ast->getNode(BySetPosNode::class);
     *
     * // BYSETPOS selects first and last occurrences from BYDAY expansion
     * ```
     * @example Validation error handling
     * ```php
     * try {
     *     $parser->parse('INTERVAL=2'); // Missing FREQ
     * } catch (ValidationException $e) {
     *     echo $e->getMessage(); // 'FREQ parameter is required'
     * }
     * ```
     *
     * @see RruleAst For working with the returned AST
     * @see Tokenizer::tokenize() For the tokenization process
     */
    public function parse(string $rruleString): RruleAst
    {
        $tokens = $this->tokenizer->tokenize($rruleString);

        $this->validateRequiredParameters($tokens);
        $this->validateMutuallyExclusiveParameters($tokens);
        $this->validateBySetPosRequirements($tokens);

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

    /**
     * @param array<string, string> $tokens
     */
    private function validateBySetPosRequirements(array $tokens): void
    {
        if (isset($tokens['BYSETPOS'])) {
            // BYSETPOS requires at least one expandable BY* rule
            $hasExpandableByRule = isset($tokens['BYDAY'])
                || isset($tokens['BYMONTHDAY'])
                || isset($tokens['BYMONTH'])
                || isset($tokens['BYWEEKNO']);

            if (!$hasExpandableByRule) {
                throw new ValidationException(
                    new class implements Node {
                        public function getName(): string
                        {
                            return 'BYSETPOS';
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
                    'BYSETPOS requires at least one of BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO to be specified'
                );
            }
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
