<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\RruleParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RruleParserTest extends TestCase
{
    #[DataProvider('provideValidRruleStrings')]
    public function testParseValidRruleStrings(array $expectedNodes, string $rruleString): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse($rruleString);

        // Check that all expected nodes are present
        foreach ($expectedNodes as $nodeType => $expectedValue) {
            $this->assertTrue($ast->hasNode($nodeType), "AST should contain {$nodeType} node");

            $node = $ast->getNode($nodeType);
            $this->assertEquals($expectedValue, $node->getValue(), "Node {$nodeType} should have expected value");
        }
    }

    #[DataProvider('provideInvalidRruleStrings')]
    public function testParseInvalidRruleStrings(string $expectedExceptionClass, string $expectedMessage, string $rruleString): void
    {
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedMessage);

        $parser = new RruleParser();
        $parser->parse($rruleString);
    }

    #[DataProvider('provideRequiredParameterTests')]
    public function testRequiredParameters(string $expectedMessage, string $rruleString): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $parser = new RruleParser();
        $parser->parse($rruleString);
    }

    #[DataProvider('provideMutuallyExclusiveParameterTests')]
    public function testMutuallyExclusiveParameters(string $expectedMessage, string $rruleString): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $parser = new RruleParser();
        $parser->parse($rruleString);
    }

    public function testGetNodesFromAst(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=DAILY;INTERVAL=2;COUNT=10');

        $this->assertInstanceOf(FrequencyNode::class, $ast->getNode('FREQ'));
        $this->assertInstanceOf(IntervalNode::class, $ast->getNode('INTERVAL'));
        $this->assertInstanceOf(CountNode::class, $ast->getNode('COUNT'));

        $this->assertEquals('DAILY', $ast->getNode('FREQ')->getValue());
        $this->assertEquals(2, $ast->getNode('INTERVAL')->getValue());
        $this->assertEquals(10, $ast->getNode('COUNT')->getValue());
    }

    public function testGetNonExistentNodeThrowsException(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=DAILY');

        $this->expectException(ParseException::class);
        $this->expectExceptionMessage('Node INTERVAL not found in AST');

        $ast->getNode('INTERVAL');
    }

    public static function provideValidRruleStrings(): array
    {
        return [
            'daily with defaults' => [
                ['FREQ' => 'DAILY'],
                'FREQ=DAILY',
            ],
            'daily with interval' => [
                ['FREQ' => 'DAILY', 'INTERVAL' => 2],
                'FREQ=DAILY;INTERVAL=2',
            ],
            'weekly with count' => [
                ['FREQ' => 'WEEKLY', 'COUNT' => 10],
                'FREQ=WEEKLY;COUNT=10',
            ],
            'monthly with until' => [
                ['FREQ' => 'MONTHLY', 'UNTIL' => new DateTimeImmutable('2025-12-31T23:59:59Z')],
                'FREQ=MONTHLY;UNTIL=20251231T235959Z',
            ],
            'yearly with all basic parameters' => [
                ['FREQ' => 'YEARLY', 'INTERVAL' => 3, 'COUNT' => 5],
                'FREQ=YEARLY;INTERVAL=3;COUNT=5',
            ],
            'case insensitive parameters' => [
                ['FREQ' => 'DAILY', 'INTERVAL' => 1],
                'freq=daily;interval=1',
            ],
            'whitespace handling' => [
                ['FREQ' => 'WEEKLY', 'COUNT' => 15],
                ' FREQ = WEEKLY ; COUNT = 15 ',
            ],
            'parameter order independence' => [
                ['INTERVAL' => 4, 'FREQ' => 'MONTHLY', 'COUNT' => 8],
                'INTERVAL=4;FREQ=MONTHLY;COUNT=8',
            ],
        ];
    }

    public static function provideInvalidRruleStrings(): array
    {
        return [
            'empty string' => [
                ParseException::class,
                'RRULE string cannot be empty',
                '',
            ],
            'invalid parameter format' => [
                ParseException::class,
                'Invalid parameter format: FREQ. Expected parameter=value',
                'FREQ',
            ],
            'invalid frequency' => [
                ValidationException::class,
                'Invalid frequency value: INVALID. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY',
                'FREQ=INVALID',
            ],
            'invalid interval non-numeric' => [
                ValidationException::class,
                'Interval must be a valid integer, got: abc',
                'FREQ=DAILY;INTERVAL=abc',
            ],
            'invalid interval negative' => [
                ValidationException::class,
                'Interval must be a non-negative integer, got: -1',
                'FREQ=DAILY;INTERVAL=-1',
            ],
            'invalid count non-numeric' => [
                ValidationException::class,
                'Count must be a valid integer, got: xyz',
                'FREQ=DAILY;COUNT=xyz',
            ],
            'invalid count negative' => [
                ValidationException::class,
                'Count must be a non-negative integer, got: -5',
                'FREQ=DAILY;COUNT=-5',
            ],
            'invalid until format' => [
                ValidationException::class,
                'Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 2025-12-31',
                'FREQ=DAILY;UNTIL=2025-12-31',
            ],
            'duplicate parameter' => [
                ParseException::class,
                'Duplicate parameter: FREQ',
                'FREQ=DAILY;FREQ=WEEKLY',
            ],
        ];
    }

    public static function provideRequiredParameterTests(): array
    {
        return [
            'missing frequency' => [
                'FREQ parameter is required',
                'INTERVAL=2;COUNT=10',
            ],
        ];
    }

    public static function provideMutuallyExclusiveParameterTests(): array
    {
        return [
            'count and until together' => [
                'COUNT and UNTIL are mutually exclusive',
                'FREQ=DAILY;COUNT=10;UNTIL=20251231T235959Z',
            ],
        ];
    }
}
