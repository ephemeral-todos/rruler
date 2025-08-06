<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\BySetPosNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class BySetPosNodeTest extends TestCase
{
    #[DataProvider('provideValidBySetPosData')]
    public function testValidBySetPosParsing(array $expected, string $input): void
    {
        $node = new BySetPosNode($input);

        $this->assertEquals('BYSETPOS', $node->getName());
        $this->assertEquals($expected, $node->getValue());
        $this->assertEquals($input, $node->getRawValue());
    }

    #[DataProvider('provideInvalidBySetPosData')]
    public function testInvalidBySetPosValidation(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new BySetPosNode($input);
    }

    public function testEmptyBySetPosThrowsCannotBeEmptyException(): void
    {
        $this->expectException(CannotBeEmptyException::class);

        new BySetPosNode('');
    }

    public function testGetChoicesReturnsValidPositionRange(): void
    {
        $choices = BySetPosNode::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(732, $choices); // 366 positive + 366 negative

        // Check positive range
        $this->assertContains('1', $choices);
        $this->assertContains('366', $choices);

        // Check negative range
        $this->assertContains('-1', $choices);
        $this->assertContains('-366', $choices);

        // Ensure zero is not included
        $this->assertNotContains('0', $choices);

        // Check boundary values not included
        $this->assertNotContains('367', $choices);
        $this->assertNotContains('-367', $choices);
    }

    public static function provideValidBySetPosData(): array
    {
        return [
            // Single positions - positive
            [[1], '1'],
            [[5], '5'],
            [[366], '366'],

            // Single positions - negative
            [[-1], '-1'],
            [[-5], '-5'],
            [[-366], '-366'],

            // Multiple positions
            [[1, -1], '1,-1'], // First and last
            [[1, 2, -2, -1], '1,2,-2,-1'], // First two and last two
            [[1, 3, 5], '1,3,5'], // Odd positions
            [[-1, -3, -5], '-1,-3,-5'], // Odd positions from end

            // RFC 5545 examples
            [[-1], '-1'], // Last occurrence (most common)
            [[1, -1], '1,-1'], // First and last
            [[2, 4], '2,4'], // Even positions

            // Values with spaces
            [[1, -1], '1, -1'],
            [[1, -1], ' 1 , -1 '],
            [[1, 2, -1], '1, 2, -1'],

            // Edge cases
            [[1, 366], '1,366'], // First and last possible positive
            [[-1, -366], '-1,-366'], // First and last possible negative
            [[1, 2, 3, -3, -2, -1], '1,2,3,-3,-2,-1'], // Complex selection
        ];
    }

    public static function provideInvalidBySetPosData(): array
    {
        return [
            // Zero value (not allowed per RFC 5545)
            ['Position value cannot be zero', '0'],
            ['Position value cannot be zero', '1,0,-1'],

            // Out of range values (beyond reasonable bounds)
            ['Position value must be between -366 and 366, got: 367', '367'],
            ['Position value must be between -366 and 366, got: 1000', '1000'],
            ['Position value must be between -366 and 366, got: -367', '-367'],
            ['Position value must be between -366 and 366, got: -1000', '-1000'],
            ['Position value must be between -366 and 366, got: 367', '1,367,-1'],

            // Invalid formats
            ['Invalid position format: abc', 'abc'],
            ['Invalid position format: 1.5', '1.5'],
            ['Invalid position format: 1a', '1a'],
            ['Invalid position format: +1', '+1'],
            ['Invalid position format: --1', '--1'],
            ['Invalid position format: -+1', '-+1'],

            // Empty components in comma-separated values
            ['BYSETPOS cannot contain empty position specifications', '1,,-1'],
            ['BYSETPOS cannot contain empty position specifications', ',1,-1'],
            ['BYSETPOS cannot contain empty position specifications', '1,-1,'],
            ['BYSETPOS cannot contain empty position specifications', '1, ,-1'],
        ];
    }
}
