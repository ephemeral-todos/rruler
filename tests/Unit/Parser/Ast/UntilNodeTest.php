<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class UntilNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(DateTimeImmutable $expected, string $input): void
    {
        $node = new UntilNode($input);

        $this->assertEquals($expected, $node->getValue());
        $node->validate(); // Should not throw
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $node = new UntilNode($input);
        $node->validate();
    }

    public static function provideHappyPathData(): array
    {
        return [
            [new DateTimeImmutable('2024-12-31T23:59:59Z'), '20241231T235959Z'],
            [new DateTimeImmutable('2025-01-01T00:00:00Z'), '20250101T000000Z'],
            [new DateTimeImmutable('2024-06-15T12:30:45Z'), '20240615T123045Z'],
            [new DateTimeImmutable('2030-12-25T18:00:00Z'), '20301225T180000Z'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Until cannot be empty', ''],
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 2024-12-31', '2024-12-31'],
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 20241231', '20241231'],
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 20241231T235959', '20241231T235959'],
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: abc', 'abc'],
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 20241301T235959Z', '20241301T235959Z'], // Invalid month
            ['Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: 20241231T255959Z', '20241231T255959Z'], // Invalid hour
        ];
    }
}
