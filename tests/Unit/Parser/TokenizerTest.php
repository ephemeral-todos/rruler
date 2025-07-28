<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser;

use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Parser\Tokenizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class TokenizerTest extends TestCase
{
    #[DataProvider('provideValidRruleStrings')]
    public function testTokenizeValidRruleStrings(array $expectedTokens, string $rruleString): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($rruleString);

        $this->assertEquals($expectedTokens, $tokens);
    }

    #[DataProvider('provideInvalidRruleStrings')]
    public function testTokenizeInvalidRruleStrings(string $expectedMessage, string $rruleString): void
    {
        $this->expectException(ParseException::class);
        $this->expectExceptionMessage($expectedMessage);

        $tokenizer = new Tokenizer();
        $tokenizer->tokenize($rruleString);
    }

    #[DataProvider('provideWhitespaceHandlingData')]
    public function testWhitespaceHandling(array $expectedTokens, string $rruleString): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($rruleString);

        $this->assertEquals($expectedTokens, $tokens);
    }

    #[DataProvider('provideCaseInsensitivityData')]
    public function testCaseInsensitivity(array $expectedTokens, string $rruleString): void
    {
        $tokenizer = new Tokenizer();
        $tokens = $tokenizer->tokenize($rruleString);

        $this->assertEquals($expectedTokens, $tokens);
    }

    public static function provideValidRruleStrings(): array
    {
        return [
            'single parameter' => [
                ['FREQ' => 'DAILY'],
                'FREQ=DAILY',
            ],
            'multiple parameters' => [
                ['FREQ' => 'DAILY', 'INTERVAL' => '2'],
                'FREQ=DAILY;INTERVAL=2',
            ],
            'all basic parameters' => [
                ['FREQ' => 'WEEKLY', 'INTERVAL' => '1', 'COUNT' => '10'],
                'FREQ=WEEKLY;INTERVAL=1;COUNT=10',
            ],
            'with until parameter' => [
                ['FREQ' => 'MONTHLY', 'UNTIL' => '20251231T235959Z'],
                'FREQ=MONTHLY;UNTIL=20251231T235959Z',
            ],
            'complex parameter order' => [
                ['COUNT' => '5', 'FREQ' => 'YEARLY', 'INTERVAL' => '3'],
                'COUNT=5;FREQ=YEARLY;INTERVAL=3',
            ],
        ];
    }

    public static function provideInvalidRruleStrings(): array
    {
        return [
            'empty string' => [
                'RRULE string cannot be empty',
                '',
            ],
            'missing value' => [
                'Invalid parameter format: FREQ=. Expected parameter=value',
                'FREQ=',
            ],
            'missing parameter name' => [
                'Invalid parameter format: =DAILY. Expected parameter=value',
                '=DAILY',
            ],
            'missing equals sign' => [
                'Invalid parameter format: FREQ. Expected parameter=value',
                'FREQ',
            ],
            'multiple equals signs' => [
                'Invalid parameter format: FREQ=DAILY=EXTRA. Expected parameter=value',
                'FREQ=DAILY=EXTRA',
            ],
            'duplicate parameter' => [
                'Duplicate parameter: FREQ',
                'FREQ=DAILY;FREQ=WEEKLY',
            ],
        ];
    }

    public static function provideWhitespaceHandlingData(): array
    {
        return [
            'spaces around parameters' => [
                ['FREQ' => 'DAILY', 'INTERVAL' => '2'],
                ' FREQ=DAILY ; INTERVAL=2 ',
            ],
            'spaces around equals' => [
                ['FREQ' => 'WEEKLY'],
                'FREQ = WEEKLY',
            ],
            'tabs and spaces mixed' => [
                ['FREQ' => 'MONTHLY', 'COUNT' => '5'],
                "\tFREQ=MONTHLY\t;\tCOUNT=5\t",
            ],
            'newlines and spaces' => [
                ['FREQ' => 'YEARLY'],
                "\nFREQ=YEARLY\n",
            ],
        ];
    }

    public static function provideCaseInsensitivityData(): array
    {
        return [
            'lowercase parameters' => [
                ['FREQ' => 'DAILY', 'INTERVAL' => '1'],
                'freq=daily;interval=1',
            ],
            'mixed case parameters' => [
                ['FREQ' => 'WEEKLY', 'COUNT' => '10'],
                'Freq=Weekly;Count=10',
            ],
            'uppercase values' => [
                ['FREQ' => 'MONTHLY', 'UNTIL' => '20251231T235959Z'],
                'FREQ=MONTHLY;UNTIL=20251231T235959Z',
            ],
        ];
    }
}
