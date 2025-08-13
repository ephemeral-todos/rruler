<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthDayNode;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthNode;
use EphemeralTodos\Rruler\Parser\Ast\ByWeekNoNode;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\WkstNode;
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
    public function testRequiredParameters(string $behaviorDescription, string $rruleString): void
    {
        // Behavioral test: parser should reject RRULE strings missing required parameters
        $this->expectException(ValidationException::class);

        $parser = new RruleParser();

        try {
            $parser->parse($rruleString);
            $this->fail('Expected ValidationException for missing required parameter in: '.$rruleString);
        } catch (ValidationException $e) {
            // Validate exception behavior without testing exact message
            $this->assertNotEmpty($e->getMessage(), 'Exception should explain validation failure');
            $this->assertStringContainsString('required', strtolower($e->getMessage()), 'Exception should indicate required parameter issue');
            throw $e; // Re-throw to satisfy expectException
        }
    }

    #[DataProvider('provideMutuallyExclusiveParameterTests')]
    public function testMutuallyExclusiveParameters(string $behaviorDescription, string $rruleString): void
    {
        // Behavioral test: parser should reject RRULE with mutually exclusive parameters
        $this->expectException(ValidationException::class);

        $parser = new RruleParser();

        try {
            $parser->parse($rruleString);
            $this->fail('Expected ValidationException for mutually exclusive parameters in: '.$rruleString);
        } catch (ValidationException $e) {
            // Validate exception behavior without testing exact message
            $this->assertNotEmpty($e->getMessage(), 'Exception should explain validation failure');
            $this->assertTrue(
                str_contains(strtolower($e->getMessage()), 'exclusive')
                || str_contains(strtolower($e->getMessage()), 'conflict')
                || str_contains(strtolower($e->getMessage()), 'cannot'),
                'Exception should indicate parameter conflict'
            );
            throw $e; // Re-throw to satisfy expectException
        }
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

    public function testParseByWeekNoParameter(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=YEARLY;BYWEEKNO=13,26,39,52');

        $this->assertTrue($ast->hasNode('BYWEEKNO'));
        $this->assertInstanceOf(ByWeekNoNode::class, $ast->getNode('BYWEEKNO'));
        $this->assertEquals([13, 26, 39, 52], $ast->getNode('BYWEEKNO')->getValue());
    }

    public function testParseWkstParameter(): void
    {
        $parser = new RruleParser();
        $ast = $parser->parse('FREQ=WEEKLY;WKST=SU');

        $this->assertTrue($ast->hasNode('WKST'));
        $this->assertInstanceOf(WkstNode::class, $ast->getNode('WKST'));
        $this->assertEquals('SU', $ast->getNode('WKST')->getValue());
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
            'yearly with byweekno single value' => [
                ['FREQ' => 'YEARLY', 'BYWEEKNO' => [26]],
                'FREQ=YEARLY;BYWEEKNO=26',
            ],
            'yearly with byweekno multiple values' => [
                ['FREQ' => 'YEARLY', 'BYWEEKNO' => [13, 26, 39, 52]],
                'FREQ=YEARLY;BYWEEKNO=13,26,39,52',
            ],
            'yearly with byweekno quarterly pattern' => [
                ['FREQ' => 'YEARLY', 'BYWEEKNO' => [1, 13, 26, 39, 52]],
                'FREQ=YEARLY;BYWEEKNO=1,13,26,39,52',
            ],
            'byweekno with spaces' => [
                ['FREQ' => 'YEARLY', 'BYWEEKNO' => [1, 26, 53]],
                'FREQ=YEARLY;BYWEEKNO=1, 26, 53',
            ],
            'weekly with wkst sunday' => [
                ['FREQ' => 'WEEKLY', 'WKST' => 'SU'],
                'FREQ=WEEKLY;WKST=SU',
            ],
            'weekly with wkst monday' => [
                ['FREQ' => 'WEEKLY', 'WKST' => 'MO'],
                'FREQ=WEEKLY;WKST=MO',
            ],
            'weekly with wkst friday' => [
                ['FREQ' => 'WEEKLY', 'WKST' => 'FR'],
                'FREQ=WEEKLY;WKST=FR',
            ],
            'wkst with spaces' => [
                ['FREQ' => 'WEEKLY', 'WKST' => 'TU'],
                ' FREQ = WEEKLY ; WKST = TU ',
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
            'invalid byweekno zero' => [
                ValidationException::class,
                'Week number cannot be zero',
                'FREQ=YEARLY;BYWEEKNO=0',
            ],
            'invalid byweekno out of range positive' => [
                ValidationException::class,
                'Week number must be between 1-53, got: 54',
                'FREQ=YEARLY;BYWEEKNO=54',
            ],
            'invalid byweekno out of range negative' => [
                ValidationException::class,
                'Week number must be between 1-53, got: -1',
                'FREQ=YEARLY;BYWEEKNO=-1',
            ],
            'invalid byweekno format' => [
                ValidationException::class,
                'Invalid week number format: abc',
                'FREQ=YEARLY;BYWEEKNO=abc',
            ],
            'invalid byweekno empty component' => [
                ValidationException::class,
                'BYWEEKNO cannot contain empty week specifications',
                'FREQ=YEARLY;BYWEEKNO=1,,26',
            ],
            'invalid wkst empty' => [
                ParseException::class,
                'Invalid parameter format: WKST=. Expected parameter=value',
                'FREQ=WEEKLY;WKST=',
            ],
            'invalid wkst invalid value' => [
                ValidationException::class,
                'Invalid week start day value: INVALID. Valid values are: SU, MO, TU, WE, TH, FR, SA',
                'FREQ=WEEKLY;WKST=INVALID',
            ],
            'invalid wkst lowercase' => [
                ValidationException::class,
                'Invalid week start day value: mo. Valid values are: SU, MO, TU, WE, TH, FR, SA',
                'FREQ=WEEKLY;WKST=mo',
            ],
            'invalid wkst full name' => [
                ValidationException::class,
                'Invalid week start day value: MONDAY. Valid values are: SU, MO, TU, WE, TH, FR, SA',
                'FREQ=WEEKLY;WKST=MONDAY',
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
