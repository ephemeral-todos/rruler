<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByMonthNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByMonthNodeTest extends TestCase
{
    #[DataProvider('provideValidByMonthData')]
    public function testValidByMonthParsing(array $expected, string $input): void
    {
        $node = new ByMonthNode($input);

        $this->assertEquals('BYMONTH', $node->getName());
        $this->assertEquals($expected, $node->getValue());
        $this->assertEquals($input, $node->getRawValue());
    }

    #[DataProvider('provideInvalidByMonthData')]
    public function testInvalidByMonthValidation(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ByMonthNode($input);
    }

    public function testEmptyByMonthThrowsCannotBeEmptyException(): void
    {
        $this->expectException(CannotBeEmptyException::class);

        new ByMonthNode('');
    }

    public function testGetChoicesReturnsValidMonthRange(): void
    {
        $choices = ByMonthNode::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(12, $choices); // 1-12
        $this->assertEquals(['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'], $choices);
        $this->assertContains('1', $choices);
        $this->assertContains('12', $choices);
        $this->assertNotContains('0', $choices);
        $this->assertNotContains('13', $choices);
    }

    public static function provideValidByMonthData(): array
    {
        return [
            // Single months
            [[1], '1'],
            [[6], '6'],
            [[12], '12'],

            // Multiple months
            [[1, 6, 12], '1,6,12'],
            [[3, 6, 9, 12], '3,6,9,12'], // Quarterly
            [[1, 2, 3], '1,2,3'], // Q1
            [[6, 7, 8], '6,7,8'], // Summer months

            // Values with spaces
            [[1, 6], '1, 6'],
            [[1, 6], ' 1 , 6 '],
            [[3, 6, 9], '3, 6, 9'],

            // All months
            [[1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12], '1,2,3,4,5,6,7,8,9,10,11,12'],
        ];
    }

    public static function provideInvalidByMonthData(): array
    {
        return [
            // Zero value
            ['Month value cannot be zero', '0'],
            ['Month value cannot be zero', '1,0,6'],

            // Out of range values
            ['Month value must be between 1-12, got: 13', '13'],
            ['Month value must be between 1-12, got: 25', '25'],
            ['Month value must be between 1-12, got: 13', '1,13,6'],

            // Negative values
            ['Month value must be between 1-12, got: -1', '-1'],
            ['Month value must be between 1-12, got: -12', '-12'],

            // Invalid formats
            ['Invalid month value format: abc', 'abc'],
            ['Invalid month value format: 1.5', '1.5'],
            ['Invalid month value format: 1a', '1a'],
            ['Invalid month value format: +1', '+1'],

            // Empty components in comma-separated values
            ['BYMONTH cannot contain empty month specifications', '1,,6'],
            ['BYMONTH cannot contain empty month specifications', ',1,6'],
            ['BYMONTH cannot contain empty month specifications', '1,6,'],
            ['BYMONTH cannot contain empty month specifications', '1, ,6'],
        ];
    }
}
