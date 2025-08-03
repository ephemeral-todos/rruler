<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthNode;
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

    public function testParseByMonthDayParameter(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=MONTHLY;BYMONTHDAY=1,15,-1');

        $this->assertTrue($ast->hasNode('BYMONTHDAY'));
        $this->assertInstanceOf(ByMonthDayNode::class, $ast->getNode('BYMONTHDAY'));
        $this->assertEquals([1, 15, -1], $ast->getNode('BYMONTHDAY')->getValue());
    }

    public function testParseByMonthParameter(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=YEARLY;BYMONTH=3,6,9,12');

        $this->assertTrue($ast->hasNode('BYMONTH'));
        $this->assertInstanceOf(ByMonthNode::class, $ast->getNode('BYMONTH'));
        $this->assertEquals([3, 6, 9, 12], $ast->getNode('BYMONTH')->getValue());
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
            'whitespace handling' => [
                ['FREQ' => 'WEEKLY', 'COUNT' => 15],
                ' FREQ = WEEKLY ; COUNT = 15 ',
            ],
            'parameter order independence' => [
                ['INTERVAL' => 4, 'FREQ' => 'MONTHLY', 'COUNT' => 8],
                'INTERVAL=4;FREQ=MONTHLY;COUNT=8',
            ],
            'monthly with bymonthday single value' => [
                ['FREQ' => 'MONTHLY', 'BYMONTHDAY' => [15]],
                'FREQ=MONTHLY;BYMONTHDAY=15',
            ],
            'monthly with bymonthday multiple values' => [
                ['FREQ' => 'MONTHLY', 'BYMONTHDAY' => [1, 15, -1]],
                'FREQ=MONTHLY;BYMONTHDAY=1,15,-1',
            ],
            'yearly with bymonthday negative values' => [
                ['FREQ' => 'YEARLY', 'BYMONTHDAY' => [-1, -15, -31]],
                'FREQ=YEARLY;BYMONTHDAY=-1,-15,-31',
            ],
            'bymonthday with spaces' => [
                ['FREQ' => 'MONTHLY', 'BYMONTHDAY' => [1, 15, -1]],
                'FREQ=MONTHLY;BYMONTHDAY=1, 15, -1',
            ],
            'yearly with bymonth single value' => [
                ['FREQ' => 'YEARLY', 'BYMONTH' => [6]],
                'FREQ=YEARLY;BYMONTH=6',
            ],
            'yearly with bymonth multiple values' => [
                ['FREQ' => 'YEARLY', 'BYMONTH' => [3, 6, 9, 12]],
                'FREQ=YEARLY;BYMONTH=3,6,9,12',
            ],
            'yearly with bymonth all months' => [
                ['FREQ' => 'YEARLY', 'BYMONTH' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]],
                'FREQ=YEARLY;BYMONTH=1,2,3,4,5,6,7,8,9,10,11,12',
            ],
            'bymonth with spaces' => [
                ['FREQ' => 'YEARLY', 'BYMONTH' => [1, 6, 12]],
                'FREQ=YEARLY;BYMONTH=1, 6, 12',
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
            'case sensitive frequency' => [
                ValidationException::class,
                'Invalid frequency value: daily. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY',
                'freq=daily',
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
            'invalid bymonthday zero' => [
                ValidationException::class,
                'Day value cannot be zero',
                'FREQ=MONTHLY;BYMONTHDAY=0',
            ],
            'invalid bymonthday out of range positive' => [
                ValidationException::class,
                'Day value must be between 1-31 or -1 to -31, got: 32',
                'FREQ=MONTHLY;BYMONTHDAY=32',
            ],
            'invalid bymonthday out of range negative' => [
                ValidationException::class,
                'Day value must be between 1-31 or -1 to -31, got: -32',
                'FREQ=MONTHLY;BYMONTHDAY=-32',
            ],
            'invalid bymonthday format' => [
                ValidationException::class,
                'Invalid day value format: abc',
                'FREQ=MONTHLY;BYMONTHDAY=abc',
            ],
            'invalid bymonthday empty component' => [
                ValidationException::class,
                'BYMONTHDAY cannot contain empty day specifications',
                'FREQ=MONTHLY;BYMONTHDAY=1,,15',
            ],
            'invalid bymonth zero' => [
                ValidationException::class,
                'Month value cannot be zero',
                'FREQ=YEARLY;BYMONTH=0',
            ],
            'invalid bymonth out of range positive' => [
                ValidationException::class,
                'Month value must be between 1-12, got: 13',
                'FREQ=YEARLY;BYMONTH=13',
            ],
            'invalid bymonth out of range negative' => [
                ValidationException::class,
                'Month value must be between 1-12, got: -1',
                'FREQ=YEARLY;BYMONTH=-1',
            ],
            'invalid bymonth format' => [
                ValidationException::class,
                'Invalid month value format: abc',
                'FREQ=YEARLY;BYMONTH=abc',
            ],
            'invalid bymonth empty component' => [
                ValidationException::class,
                'BYMONTH cannot contain empty month specifications',
                'FREQ=YEARLY;BYMONTH=1,,6',
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
