<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefaultOccurrenceGeneratorBySetPosValidationTest extends TestCase
{
    use TestRrulerBehavior;

    #[DataProvider('provideBySetPosWithoutByRulesData')]
    public function testBySetPosRequiresOtherByRules(string $rruleString, string $expectedMessage): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->testRruler->parse($rruleString);
    }

    #[DataProvider('provideInvalidBySetPosUsageData')]
    public function testInvalidBySetPosUsage(string $rruleString, string $expectedExceptionClass, string $expectedMessage): void
    {
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedMessage);

        $this->testRruler->parse($rruleString);
    }

    public static function provideBySetPosWithoutByRulesData(): array
    {
        return [
            // BYSETPOS without any BY* rules should be rejected
            'BYSETPOS without BY rules - daily' => [
                'FREQ=DAILY;BYSETPOS=1',
                'BYSETPOS requires at least one of BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO to be specified',
            ],
            'BYSETPOS without BY rules - weekly' => [
                'FREQ=WEEKLY;BYSETPOS=-1',
                'BYSETPOS requires at least one of BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO to be specified',
            ],
            'BYSETPOS without BY rules - monthly' => [
                'FREQ=MONTHLY;BYSETPOS=2',
                'BYSETPOS requires at least one of BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO to be specified',
            ],
            'BYSETPOS without BY rules - yearly' => [
                'FREQ=YEARLY;BYSETPOS=1,3,-1',
                'BYSETPOS requires at least one of BYDAY, BYMONTHDAY, BYMONTH, or BYWEEKNO to be specified',
            ],
        ];
    }

    public static function provideInvalidBySetPosUsageData(): array
    {
        return [
            // Position value validation (already tested in BySetPosNodeTest, but good to verify end-to-end)
            'zero position' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=0',
                ValidationException::class,
                'Position value cannot be zero',
            ],
            'position too large' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=367',
                ValidationException::class,
                'Position value must be between -366 and 366, got: 367',
            ],
            'position too small' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=-367',
                ValidationException::class,
                'Position value must be between -366 and 366, got: -367',
            ],
            'invalid format - decimal' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1.5',
                ValidationException::class,
                'Invalid position format: 1.5',
            ],
            'invalid format - text' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=first',
                ValidationException::class,
                'Invalid position format: first',
            ],
            'invalid format - leading plus' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=+1',
                ValidationException::class,
                'Invalid position format: +1',
            ],
            'empty position in list' => [
                'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1,,3',
                ValidationException::class,
                'BYSETPOS cannot contain empty position specifications',
            ],
        ];
    }
}
