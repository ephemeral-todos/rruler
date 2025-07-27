<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Exception;

use EphemeralTodos\Rruler\Exception\InvalidIntegerException;
use EphemeralTodos\Rruler\Exception\RrulerException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class InvalidIntegerExceptionTest extends TestCase
{
    #[DataProvider('provideExceptionData')]
    public function testExceptionMessage(
        string $expected,
        string|object $type,
        string $invalidValue,
        bool $mustBePositive = false,
    ): void {
        $exception = new InvalidIntegerException($type, $invalidValue, $mustBePositive);

        $this->assertEquals($expected, $exception->getMessage());
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertInstanceOf(RrulerException::class, $exception);
    }

    public static function provideExceptionData(): array
    {
        return [
            ['Interval must be a valid integer, got: abc', IntervalNode::class, 'abc'],
            ['Interval must be a valid integer, got: 1.5', IntervalNode::class, '1.5'],
            ['Interval must be a positive integer, got: 0', IntervalNode::class, '0', true],
            ['Interval must be a positive integer, got: -5', IntervalNode::class, '-5', true],
            ['Interval must be a valid integer, got: abc', new IntervalNode('5'), 'abc'],
            ['Unknown must be a valid integer, got: test', 'SomeRandomClass', 'test'],
            ['Unknown must be a positive integer, got: -1', 'SomeRandomClass', '-1', true],
        ];
    }
}
