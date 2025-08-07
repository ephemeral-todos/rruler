<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Property;
use EphemeralTodos\Rruler\Ical\PropertyParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PropertyParserTest extends TestCase
{
    #[DataProvider('provideBasicProperties')]
    public function testParsesBasicPropertiesCorrectly(string $propertyLine, string $expectedName, string $expectedValue, array $expectedParameters = []): void
    {
        $parser = new PropertyParser();
        $property = $parser->parse($propertyLine);

        $this->assertInstanceOf(Property::class, $property);
        $this->assertEquals($expectedName, $property->getName());
        $this->assertEquals($expectedValue, $property->getValue());
        $this->assertEquals($expectedParameters, $property->getParameters());
    }

    #[DataProvider('providePropertiesWithParameters')]
    public function testParsesPropertiesWithParameters(string $propertyLine, string $expectedName, string $expectedValue, array $expectedParameters): void
    {
        $parser = new PropertyParser();
        $property = $parser->parse($propertyLine);

        $this->assertEquals($expectedName, $property->getName());
        $this->assertEquals($expectedValue, $property->getValue());
        $this->assertEquals($expectedParameters, $property->getParameters());
    }

    #[DataProvider('provideComplexProperties')]
    public function testParsesComplexRealWorldProperties(string $propertyLine, string $expectedName, string $expectedValue, array $expectedParameters): void
    {
        $parser = new PropertyParser();
        $property = $parser->parse($propertyLine);

        $this->assertEquals($expectedName, $property->getName());
        $this->assertEquals($expectedValue, $property->getValue());
        $this->assertEquals($expectedParameters, $property->getParameters());
    }

    public function testHandlesEmptyPropertyLine(): void
    {
        $parser = new PropertyParser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Empty property line provided');

        $parser->parse('');
    }

    public function testHandlesInvalidPropertyLine(): void
    {
        $parser = new PropertyParser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid property format');

        $parser->parse('INVALID_LINE_WITHOUT_COLON');
    }

    public function testCaseInsensitivePropertyNames(): void
    {
        $parser = new PropertyParser();

        $property1 = $parser->parse('SUMMARY:Test Event');
        $property2 = $parser->parse('summary:Test Event');
        $property3 = $parser->parse('Summary:Test Event');

        $this->assertEquals('SUMMARY', $property1->getName());
        $this->assertEquals('SUMMARY', $property2->getName());
        $this->assertEquals('SUMMARY', $property3->getName());
    }

    public function testPreservesValueCaseSensitivity(): void
    {
        $parser = new PropertyParser();
        $property = $parser->parse('SUMMARY:Test Event With MiXeD cAsE');

        $this->assertEquals('Test Event With MiXeD cAsE', $property->getValue());
    }

    public static function provideBasicProperties(): array
    {
        return [
            'simple summary' => [
                'SUMMARY:Weekly Team Meeting',
                'SUMMARY',
                'Weekly Team Meeting',
                [],
            ],
            'dtstart' => [
                'DTSTART:20250107T100000Z',
                'DTSTART',
                '20250107T100000Z',
                [],
            ],
            'rrule' => [
                'RRULE:FREQ=WEEKLY;BYDAY=TU',
                'RRULE',
                'FREQ=WEEKLY;BYDAY=TU',
                [],
            ],
            'uid' => [
                'UID:12345-67890-abcdef',
                'UID',
                '12345-67890-abcdef',
                [],
            ],
            'begin component' => [
                'BEGIN:VEVENT',
                'BEGIN',
                'VEVENT',
                [],
            ],
            'end component' => [
                'END:VEVENT',
                'END',
                'VEVENT',
                [],
            ],
        ];
    }

    public static function providePropertiesWithParameters(): array
    {
        return [
            'dtstart with timezone' => [
                'DTSTART;TZID=America/New_York:20250107T100000',
                'DTSTART',
                '20250107T100000',
                ['TZID' => 'America/New_York'],
            ],
            'attendee with multiple parameters' => [
                'ATTENDEE;CN=John Doe;ROLE=REQ-PARTICIPANT:mailto:john@example.com',
                'ATTENDEE',
                'mailto:john@example.com',
                [
                    'CN' => 'John Doe',
                    'ROLE' => 'REQ-PARTICIPANT',
                ],
            ],
            'organizer with cn parameter' => [
                'ORGANIZER;CN=Jane Smith:mailto:jane@example.com',
                'ORGANIZER',
                'mailto:jane@example.com',
                ['CN' => 'Jane Smith'],
            ],
            'dtend with value type' => [
                'DTEND;VALUE=DATE:20250108',
                'DTEND',
                '20250108',
                ['VALUE' => 'DATE'],
            ],
        ];
    }

    public static function provideComplexProperties(): array
    {
        return [
            'rrule with multiple parameters' => [
                'RRULE:FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1;COUNT=12',
                'RRULE',
                'FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1;COUNT=12',
                [],
            ],
            'description with special characters' => [
                'DESCRIPTION:Meeting agenda:\nItem 1: Review\nItem 2: Planning',
                'DESCRIPTION',
                'Meeting agenda:\nItem 1: Review\nItem 2: Planning',
                [],
            ],
            'dtstart with complex timezone' => [
                'DTSTART;TZID=America/Los_Angeles;VALUE=DATE-TIME:20250107T100000',
                'DTSTART',
                '20250107T100000',
                [
                    'TZID' => 'America/Los_Angeles',
                    'VALUE' => 'DATE-TIME',
                ],
            ],
            'attendee with quoted parameter' => [
                'ATTENDEE;CN="Smith, John";ROLE=CHAIR:mailto:john.smith@example.com',
                'ATTENDEE',
                'mailto:john.smith@example.com',
                [
                    'CN' => 'Smith, John',
                    'ROLE' => 'CHAIR',
                ],
            ],
            'property with colon in value' => [
                'DESCRIPTION:The meeting URL is: https://example.com/meeting',
                'DESCRIPTION',
                'The meeting URL is: https://example.com/meeting',
                [],
            ],
            'property with equals in value' => [
                'DESCRIPTION:Formula: A=B+C where A=10',
                'DESCRIPTION',
                'Formula: A=B+C where A=10',
                [],
            ],
        ];
    }
}
