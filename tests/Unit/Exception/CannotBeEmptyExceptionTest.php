<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Exception;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\RrulerException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CannotBeEmptyExceptionTest extends TestCase
{
    #[DataProvider('provideExceptionData')]
    public function testExceptionMessage(string $expected, object $node): void
    {
        $exception = new CannotBeEmptyException($node);

        $this->assertEquals($expected, $exception->getMessage());
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertInstanceOf(RrulerException::class, $exception);
    }

    public static function provideExceptionData(): array
    {
        return [
            ['Frequency cannot be empty', new FrequencyNode('DAILY')],
            ['Interval cannot be empty', new IntervalNode('5')],
        ];
    }
}
