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
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new FrequencyNode($input);
    }

    public static function provideHappyPathData(): array
    {
        return [
            ['DAILY', 'DAILY'],
            ['WEEKLY', 'WEEKLY'],
            ['MONTHLY', 'MONTHLY'],
            ['YEARLY', 'YEARLY'],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            ['Frequency cannot be empty', ''],
            ['Invalid frequency value: INVALID. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'INVALID'],
            ['Invalid frequency value: HOURLY. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'HOURLY'],
            ['Invalid frequency value: SECONDLY. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'SECONDLY'],
            ['Invalid frequency value: daily. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'daily'],
            ['Invalid frequency value: Daily. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'Daily'],
            ['Invalid frequency value: weekly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'weekly'],
            ['Invalid frequency value: Weekly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'Weekly'],
            ['Invalid frequency value: monthly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'monthly'],
            ['Invalid frequency value: Monthly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'Monthly'],
            ['Invalid frequency value: yearly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'yearly'],
            ['Invalid frequency value: Yearly. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'Yearly'],
            ['Invalid frequency value:  DAILY. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', ' DAILY'],
            ['Invalid frequency value: DAILY . Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', 'DAILY '],
            ['Invalid frequency value:  DAILY . Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY', ' DAILY '],
        ];
    }
}
