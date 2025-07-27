<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IntervalNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(int $expected, string $input): void
    {
        $node = new IntervalNode($input);

        $this->assertEquals($expected, $node->getValue());
        $node->validate(); // Should not throw
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $node = new IntervalNode($input);
        $node->validate();
    }

    public static function provideHappyPathData(): array
    {
        return [
            [1, '1'],
            [2, '2'],
            [10, '10'],
            [100, '100'],
            [999, '999'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Interval cannot be empty', ''],
            ['Interval must be a positive integer, got: 0', '0'],
            ['Interval must be a positive integer, got: -1', '-1'],
            ['Interval must be a positive integer, got: -5', '-5'],
            ['Interval must be a valid integer, got: abc', 'abc'],
            ['Interval must be a valid integer, got: 1.5', '1.5'],
            ['Interval must be a valid integer, got: 1a', '1a'],
        ];
    }
}
