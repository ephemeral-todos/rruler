<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByWeekNoNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByWeekNoNodeTest extends TestCase
{
    #[DataProvider('provideValidByWeekNoData')]
    public function testValidByWeekNoParsing(array $expected, string $input): void
    {
        $node = new ByWeekNoNode($input);

        $this->assertEquals('BYWEEKNO', $node->getName());
        $this->assertEquals($expected, $node->getValue());
        $this->assertEquals($input, $node->getRawValue());
    }

    #[DataProvider('provideInvalidByWeekNoData')]
    public function testInvalidByWeekNoValidation(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ByWeekNoNode($input);
    }

    public function testEmptyByWeekNoThrowsCannotBeEmptyException(): void
    {
        $this->expectException(CannotBeEmptyException::class);

        new ByWeekNoNode('');
    }

    public function testGetChoicesReturnsValidWeekRange(): void
    {
        $choices = ByWeekNoNode::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(53, $choices); // 1-53
        $this->assertEquals('1', $choices[0]);
        $this->assertEquals('53', $choices[52]);
        $this->assertContains('1', $choices);
        $this->assertContains('53', $choices);
        $this->assertNotContains('0', $choices);
        $this->assertNotContains('54', $choices);
    }

    public static function provideValidByWeekNoData(): array
    {
        return [
            // Single weeks
            [[1], '1'],
            [[26], '26'],
            [[53], '53'],

            // Multiple weeks
            [[1, 26], '1,26'],
            [[13, 26, 39, 52], '13,26,39,52'], // Quarterly pattern
            [[1, 2, 3], '1,2,3'], // First weeks of year
            [[50, 51, 52, 53], '50,51,52,53'], // Last weeks of year

            // Values with spaces
            [[1, 26], '1, 26'],
            [[1, 26], ' 1 , 26 '],
            [[13, 26, 39], '13, 26, 39'],

            // Edge cases
            [[1, 53], '1,53'], // First and last possible weeks
            [[26], '26'], // Middle of year
        ];
    }

    public static function provideInvalidByWeekNoData(): array
    {
        return [
            // Zero value
            ['Week number cannot be zero', '0'],
            ['Week number cannot be zero', '1,0,26'],

            // Out of range values
            ['Week number must be between 1-53, got: 54', '54'],
            ['Week number must be between 1-53, got: 100', '100'],
            ['Week number must be between 1-53, got: 54', '1,54,26'],

            // Negative values
            ['Week number must be between 1-53, got: -1', '-1'],
            ['Week number must be between 1-53, got: -26', '-26'],

            // Invalid formats
            ['Invalid week number format: abc', 'abc'],
            ['Invalid week number format: 1.5', '1.5'],
            ['Invalid week number format: 1a', '1a'],
            ['Invalid week number format: +1', '+1'],

            // Empty components in comma-separated values
            ['BYWEEKNO cannot contain empty week specifications', '1,,26'],
            ['BYWEEKNO cannot contain empty week specifications', ',1,26'],
            ['BYWEEKNO cannot contain empty week specifications', '1,26,'],
            ['BYWEEKNO cannot contain empty week specifications', '1, ,26'],
        ];
    }
}