<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CountNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(int $expected, string $input): void
    {
        $node = new CountNode($input);

        $this->assertEquals($expected, $node->getValue());
        $node->validate(); // Should not throw
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $node = new CountNode($input);
        $node->validate();
    }

    public static function provideHappyPathData(): array
    {
        return [
            [1, '1'],
            [5, '5'],
            [10, '10'],
            [100, '100'],
            [9999, '9999'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Count cannot be empty', ''],
            ['Count must be a positive integer, got: 0', '0'],
            ['Count must be a positive integer, got: -1', '-1'],
            ['Count must be a positive integer, got: -10', '-10'],
            ['Count must be a valid integer, got: abc', 'abc'],
            ['Count must be a valid integer, got: 5.5', '5.5'],
            ['Count must be a valid integer, got: 5a', '5a'],
        ];
    }
}
