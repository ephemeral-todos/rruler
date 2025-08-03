<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthDayNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByMonthDayNodeTest extends TestCase
{
    #[DataProvider('provideValidByMonthDayData')]
    public function testValidByMonthDayParsing(array $expected, string $input): void
    {
        $node = new ByMonthDayNode($input);

        $this->assertEquals('BYMONTHDAY', $node->getName());
        $this->assertEquals($expected, $node->getValue());
        $this->assertEquals($input, $node->getRawValue());
    }

    #[DataProvider('provideInvalidByMonthDayData')]
    public function testInvalidByMonthDayValidation(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ByMonthDayNode($input);
    }

    public function testEmptyByMonthDayThrowsCannotBeEmptyException(): void
    {
        $this->expectException(CannotBeEmptyException::class);

        new ByMonthDayNode('');
    }

    public function testGetChoicesReturnsValidDayRange(): void
    {
        $choices = ByMonthDayNode::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(62, $choices); // 1-31 and -1 to -31
        $this->assertContains('1', $choices);
        $this->assertContains('31', $choices);
        $this->assertContains('-1', $choices);
        $this->assertContains('-31', $choices);
        $this->assertNotContains('0', $choices);
        $this->assertNotContains('32', $choices);
        $this->assertNotContains('-32', $choices);
    }

    public static function provideValidByMonthDayData(): array
    {
        return [
            // Single positive values
            [[1], '1'],
            [[15], '15'],
            [[31], '31'],

            // Single negative values
            [[-1], '-1'],
            [[-15], '-15'],
            [[-31], '-31'],

            // Multiple values
            [[1, 15, 31], '1,15,31'],
            [[-1, -15, -31], '-1,-15,-31'],
            [[1, -1], '1,-1'],
            [[15, -15, 30, -30], '15,-15,30,-30'],

            // Values with spaces
            [[1, 15], '1, 15'],
            [[1, 15], ' 1 , 15 '],
            [[-1, -15], '-1, -15'],

            // Mixed positive and negative
            [[1, 15, -1, -15], '1,15,-1,-15'],
            [[10, -10, 20, -20], '10,-10,20,-20'],
        ];
    }

    public static function provideInvalidByMonthDayData(): array
    {
        return [
            // Zero value
            ['Day value cannot be zero', '0'],
            ['Day value cannot be zero', '1,0,15'],
            ['Day value cannot be zero', '-1,0,-15'],

            // Out of range positive values
            ['Day value must be between 1-31 or -1 to -31, got: 32', '32'],
            ['Day value must be between 1-31 or -1 to -31, got: 100', '100'],
            ['Day value must be between 1-31 or -1 to -31, got: 32', '1,32,15'],

            // Out of range negative values
            ['Day value must be between 1-31 or -1 to -31, got: -32', '-32'],
            ['Day value must be between 1-31 or -1 to -31, got: -100', '-100'],
            ['Day value must be between 1-31 or -1 to -31, got: -32', '-1,-32,-15'],

            // Invalid formats
            ['Invalid day value format: abc', 'abc'],
            ['Invalid day value format: 1.5', '1.5'],
            ['Invalid day value format: -1.5', '-1.5'],
            ['Invalid day value format: 1a', '1a'],
            ['Invalid day value format: -1a', '-1a'],
            ['Invalid day value format: +1', '+1'],

            // Empty components in comma-separated values
            ['BYMONTHDAY cannot contain empty day specifications', '1,,15'],
            ['BYMONTHDAY cannot contain empty day specifications', ',1,15'],
            ['BYMONTHDAY cannot contain empty day specifications', '1,15,'],
            ['BYMONTHDAY cannot contain empty day specifications', '1, ,15'],
        ];
    }
}
