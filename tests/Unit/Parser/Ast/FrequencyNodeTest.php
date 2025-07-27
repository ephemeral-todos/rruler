<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class FrequencyNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(string $expected, string $input): void
    {
        $node = new FrequencyNode($input);

        $this->assertEquals($expected, $node->getValue());
        $node->validate(); // Should not throw
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $node = new FrequencyNode($input);
        $node->validate();
    }

    public static function provideHappyPathData(): array
    {
        return [
            ['DAILY', 'DAILY'],
            ['DAILY', 'daily'],
            ['DAILY', 'Daily'],
            ['WEEKLY', 'WEEKLY'],
            ['WEEKLY', 'weekly'],
            ['WEEKLY', 'Weekly'],
            ['MONTHLY', 'MONTHLY'],
            ['MONTHLY', 'monthly'],
            ['MONTHLY', 'Monthly'],
            ['YEARLY', 'YEARLY'],
            ['YEARLY', 'yearly'],
            ['YEARLY', 'Yearly'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Frequency cannot be empty', ''],
            ['Invalid frequency value: INVALID. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'INVALID'],
            ['Invalid frequency value: HOURLY. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'HOURLY'],
            ['Invalid frequency value: SECONDLY. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'SECONDLY'],
        ];
    }
}
