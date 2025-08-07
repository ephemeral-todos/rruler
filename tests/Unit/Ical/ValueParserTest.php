<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\ValueParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValueParserTest extends TestCase
{
    #[DataProvider('provideEscapedValues')]
    public function testUnescapesValues(string $input, string $expected): void
    {
        $parser = new ValueParser();
        $result = $parser->unescape($input);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideUnescapedValues')]
    public function testEscapesValues(string $input, string $expected): void
    {
        $parser = new ValueParser();
        $result = $parser->escape($input);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideListValues')]
    public function testParsesListValues(string $input, array $expected): void
    {
        $parser = new ValueParser();
        $result = $parser->parseList($input);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideListValuesWithEscaping')]
    public function testParsesListValuesWithEscaping(string $input, array $expected): void
    {
        $parser = new ValueParser();
        $result = $parser->parseList($input);

        $this->assertEquals($expected, $result);
    }

    public function testHandlesEmptyListValue(): void
    {
        $parser = new ValueParser();
        $result = $parser->parseList('');

        $this->assertEquals([''], $result);
    }

    public function testHandlesSingleItemList(): void
    {
        $parser = new ValueParser();
        $result = $parser->parseList('SINGLE_VALUE');

        $this->assertEquals(['SINGLE_VALUE'], $result);
    }

    public function testEscapeAndUnescapeAreSymmetric(): void
    {
        $parser = new ValueParser();
        $originalValues = [
            'Simple text',
            "Text with\nnewline",       // Actual newline
            "Text with\ttab",           // Actual tab
            'Text with\\backslash',     // Literal backslash
            'Text with;semicolon',
            'Text with,comma',
            'Text with "quotes"',
        ];

        foreach ($originalValues as $original) {
            $escaped = $parser->escape($original);
            $unescaped = $parser->unescape($escaped);

            $this->assertEquals($original, $unescaped,
                "Failed for: $original (escaped: $escaped)");
        }
    }

    public function testEscapesAllBackslashes(): void
    {
        $parser = new ValueParser();

        // All backslashes should be escaped
        $input = 'Text with\\,comma';
        $result = $parser->escape($input);

        // Should escape backslash and comma
        $this->assertEquals('Text with\\\\\\,comma', $result);
    }

    public static function provideEscapedValues(): array
    {
        return [
            'no escaping needed' => [
                'Simple text',
                'Simple text',
            ],
            'escaped comma' => [
                'Text with\\, comma',
                'Text with, comma',
            ],
            'escaped semicolon' => [
                'Text with\\; semicolon',
                'Text with; semicolon',
            ],
            'escaped backslash' => [
                'Text with\\\\ backslash',
                'Text with\\ backslash',
            ],
            'escaped newline' => [
                'Text with\\n newline',
                "Text with\n newline",
            ],
            'escaped tab' => [
                'Text with\\t tab',
                "Text with\t tab",
            ],
            'multiple escapes' => [
                'Text\\, with\\; multiple\\n escapes',
                "Text, with; multiple\n escapes",
            ],
            'complex example' => [
                'Meeting at 9:00\\, bring laptop\\; notes\\n- Review agenda\\n- Discuss project',
                "Meeting at 9:00, bring laptop; notes\n- Review agenda\n- Discuss project",
            ],
        ];
    }

    public static function provideUnescapedValues(): array
    {
        return [
            'no escaping needed' => [
                'Simple text',
                'Simple text',
            ],
            'comma needing escape' => [
                'Text with, comma',
                'Text with\\, comma',
            ],
            'semicolon needing escape' => [
                'Text with; semicolon',
                'Text with\\; semicolon',
            ],
            'backslash needing escape' => [
                'Text with\\ backslash',
                'Text with\\\\ backslash',
            ],
            'newline needing escape' => [
                "Text with\n newline",
                'Text with\\n newline',
            ],
            'tab needing escape' => [
                "Text with\t tab",
                'Text with\\t tab',
            ],
            'multiple characters needing escape' => [
                "Text, with; multiple\n escapes",
                'Text\\, with\\; multiple\\n escapes',
            ],
        ];
    }

    public static function provideListValues(): array
    {
        return [
            'single item' => [
                'DAILY',
                ['DAILY'],
            ],
            'multiple items' => [
                'MO,TU,WE,TH,FR',
                ['MO', 'TU', 'WE', 'TH', 'FR'],
            ],
            'numbers' => [
                '1,15,30',
                ['1', '15', '30'],
            ],
            'mixed content' => [
                'FREQ=WEEKLY,BYDAY=MO',
                ['FREQ=WEEKLY', 'BYDAY=MO'],
            ],
            'with spaces' => [
                'MO, TU, WE',
                ['MO', 'TU', 'WE'],
            ],
        ];
    }

    public static function provideListValuesWithEscaping(): array
    {
        return [
            'escaped comma in item' => [
                'Item with\\, comma,Second item',
                ['Item with, comma', 'Second item'],
            ],
            'multiple escaped commas' => [
                'First\\, item,Second\\, item,Third item',
                ['First, item', 'Second, item', 'Third item'],
            ],
            'mixed escaping' => [
                'Simple,Complex\\, with\\; chars,Another\\n item',
                ['Simple', 'Complex, with; chars', "Another\n item"],
            ],
        ];
    }
}
