<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Exception;

use EphemeralTodos\Rruler\Exception\InvalidIntegerException;
use EphemeralTodos\Rruler\Exception\RrulerException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvalidIntegerExceptionTest extends TestCase
{
    #[DataProvider('provideExceptionData')]
    public function testExceptionMessage(
        string $expected,
        object $node,
        string $invalidValue,
        bool $mustBePositive = false,
    ): void {
        $exception = new InvalidIntegerException($node, $invalidValue, $mustBePositive);

        $this->assertEquals($expected, $exception->getMessage());
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertInstanceOf(RrulerException::class, $exception);
    }

    public static function provideExceptionData(): array
    {
        return [
            ['Interval must be a valid integer, got: abc', new IntervalNode('5'), 'abc'],
            ['Interval must be a valid integer, got: 1.5', new IntervalNode('5'), '1.5'],
            ['Interval must be a positive integer, got: 0', new IntervalNode('5'), '0', true],
            ['Interval must be a positive integer, got: -5', new IntervalNode('5'), '-5', true],
            ['Count must be a valid integer, got: abc', new CountNode('10'), 'abc'],
            ['Count must be a positive integer, got: -1', new CountNode('10'), '-1', true],
        ];
    }
}
