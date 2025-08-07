<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\ComponentType;
use EphemeralTodos\Rruler\Ical\DateTimeContext;
use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Rrule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class IcalParserTest extends TestCase
{
    #[DataProvider('provideVEventComponents')]
    public function testParsesCompleteVEventComponents(
        string $icalData,
        string $expectedSummary,
        string $expectedDtStart,
        ?string $expectedTimezone,
        ?string $expectedRrule,
    ): void {
        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        $this->assertIsArray($results);
        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertArrayHasKey('component', $result);
        $this->assertArrayHasKey('dateTimeContext', $result);

        // Check component
        $component = $result['component'];
        $this->assertInstanceOf(Component::class, $component);
        $this->assertEquals('VEVENT', $component->getType());
        $summaryProperty = $component->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals($expectedSummary, $summaryProperty->getValue());

        // Check DateTimeContext
        $dateTimeContext = $result['dateTimeContext'];
        $this->assertInstanceOf(DateTimeContext::class, $dateTimeContext);
        $this->assertEquals($expectedDtStart, $dateTimeContext->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedTimezone, $dateTimeContext->getTimezone());
        $this->assertEquals(ComponentType::VEVENT, $dateTimeContext->getComponentType());

        // Check RRULE if present
        if ($expectedRrule !== null) {
            $this->assertArrayHasKey('rrule', $result);
            $this->assertInstanceOf(Rrule::class, $result['rrule']);
            $this->assertRruleStringsEquivalent($expectedRrule, (string) $result['rrule']);
        } else {
            $this->assertArrayNotHasKey('rrule', $result);
        }
    }

    #[DataProvider('provideVTodoComponents')]
    public function testParsesCompleteVTodoComponents(
        string $icalData,
        string $expectedSummary,
        string $expectedDue,
        ?string $expectedTimezone,
        ?string $expectedRrule,
    ): void {
        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        $this->assertCount(1, $results);
        $result = $results[0];

        // Check component
        $component = $result['component'];
        $this->assertEquals('VTODO', $component->getType());
        $summaryProperty = $component->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals($expectedSummary, $summaryProperty->getValue());

        // Check DateTimeContext - should be using DUE for VTODO
        $dateTimeContext = $result['dateTimeContext'];
        $this->assertEquals($expectedDue, $dateTimeContext->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedTimezone, $dateTimeContext->getTimezone());
        $this->assertEquals(ComponentType::VTODO, $dateTimeContext->getComponentType());

        // Check RRULE if present
        if ($expectedRrule !== null) {
            $this->assertArrayHasKey('rrule', $result);
            $this->assertRruleStringsEquivalent($expectedRrule, (string) $result['rrule']);
        } else {
            $this->assertArrayNotHasKey('rrule', $result);
        }
    }

    public function testHandlesMalformedICalendarData(): void
    {
        $parser = new IcalParser();

        // Test empty string
        $results = $parser->parse('');
        $this->assertEmpty($results);

        // Test invalid component boundaries
        $malformedData = "BEGIN:VEVENT\nSUMMARY:Incomplete Event\n";
        $results = $parser->parse($malformedData);
        $this->assertEmpty($results);

        // Test component with no required properties
        $malformedData = "BEGIN:VEVENT\nEND:VEVENT\n";
        $results = $parser->parse($malformedData);
        $this->assertEmpty($results);
    }

    public function testIgnoresIrrelevantProperties(): void
    {
        $icalData = <<<ICAL
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:Daily Meeting
        RRULE:FREQ=DAILY;COUNT=5
        DESCRIPTION:This is a long description that should be ignored
        LOCATION:Conference Room A
        ORGANIZER:john@example.com
        ATTENDEE:jane@example.com
        STATUS:CONFIRMED
        SEQUENCE:0
        CREATED:20250101T000000Z
        LAST-MODIFIED:20250101T000000Z
        UID:12345@example.com
        END:VEVENT
        ICAL;

        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        $this->assertCount(1, $results);
        $result = $results[0];
        $component = $result['component'];

        // Should have relevant properties
        $this->assertTrue($component->hasProperty('DTSTART'));
        $this->assertTrue($component->hasProperty('SUMMARY'));
        $this->assertTrue($component->hasProperty('RRULE'));

        // Should ignore irrelevant properties (they may still be present but not processed)
        $summaryProperty = $component->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals('Daily Meeting', $summaryProperty->getValue());
        $this->assertEquals('2025-01-07 10:00:00', $result['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertRruleStringsEquivalent('FREQ=DAILY;COUNT=5', (string) $result['rrule']);
    }

    public function testIgnoresIrrelevantComponents(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Example Corp//Example Calendar//EN
        BEGIN:VTIMEZONE
        TZID:America/New_York
        BEGIN:STANDARD
        DTSTART:20201101T020000
        TZOFFSETFROM:-0400
        TZOFFSETTO:-0500
        RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
        TZNAME:EST
        END:STANDARD
        END:VTIMEZONE
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:Important Event
        RRULE:FREQ=WEEKLY
        END:VEVENT
        BEGIN:VFREEBUSY
        DTSTART:20250101T000000Z
        DTEND:20250131T235959Z
        END:VFREEBUSY
        END:VCALENDAR
        ICAL;

        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        // Should only extract the VEVENT, ignoring VTIMEZONE, VFREEBUSY, and VCALENDAR
        $this->assertCount(1, $results);
        $result = $results[0];

        $this->assertEquals('VEVENT', $result['component']->getType());
        $summaryProperty = $result['component']->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals('Important Event', $summaryProperty->getValue());
    }

    public function testHandlesMultipleComponents(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:Daily Event
        RRULE:FREQ=DAILY;COUNT=3
        END:VEVENT
        BEGIN:VTODO
        DUE:20250107T180000Z
        SUMMARY:Weekly Task
        RRULE:FREQ=WEEKLY;BYDAY=FR
        END:VTODO
        END:VCALENDAR
        ICAL;

        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        $this->assertCount(2, $results);

        // First result should be VEVENT
        $eventResult = $results[0];
        $this->assertEquals('VEVENT', $eventResult['component']->getType());
        $eventSummaryProperty = $eventResult['component']->getProperty('SUMMARY');
        $this->assertNotNull($eventSummaryProperty);
        $this->assertEquals('Daily Event', $eventSummaryProperty->getValue());
        $this->assertRruleStringsEquivalent('FREQ=DAILY;COUNT=3', (string) $eventResult['rrule']);

        // Second result should be VTODO
        $todoResult = $results[1];
        $this->assertEquals('VTODO', $todoResult['component']->getType());
        $todoSummaryProperty = $todoResult['component']->getProperty('SUMMARY');
        $this->assertNotNull($todoSummaryProperty);
        $this->assertEquals('Weekly Task', $todoSummaryProperty->getValue());
        $this->assertRruleStringsEquivalent('FREQ=WEEKLY;BYDAY=FR', (string) $todoResult['rrule']);
    }

    public function testHandlesComponentsWithoutRRule(): void
    {
        $icalData = <<<ICAL
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:One-time Event
        END:VEVENT
        ICAL;

        $parser = new IcalParser();
        $results = $parser->parse($icalData);

        $this->assertCount(1, $results);
        $result = $results[0];

        $this->assertArrayHasKey('component', $result);
        $this->assertArrayHasKey('dateTimeContext', $result);
        $this->assertArrayNotHasKey('rrule', $result); // No RRULE should be present

        $summaryProperty = $result['component']->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals('One-time Event', $summaryProperty->getValue());
    }

    /**
     * Helper method to compare RRULE strings ignoring parameter order.
     */
    private function assertRruleStringsEquivalent(string $expected, string $actual): void
    {
        $expectedParams = $this->parseRruleString($expected);
        $actualParams = $this->parseRruleString($actual);

        $this->assertEquals($expectedParams, $actualParams,
            "RRULE parameters should match regardless of order. Expected: {$expected}, Actual: {$actual}");
    }

    /**
     * Parse an RRULE string into an associative array of parameters.
     */
    private function parseRruleString(string $rrule): array
    {
        $params = [];
        $parts = explode(';', $rrule);

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part, 2);
                $params[$key] = $value;
            }
        }

        return $params;
    }

    public static function provideVEventComponents(): array
    {
        return [
            'Simple VEVENT with DTSTART and RRULE' => [
                <<<ICAL
                BEGIN:VEVENT
                DTSTART:20250107T100000Z
                SUMMARY:Daily Meeting
                RRULE:FREQ=DAILY;COUNT=5
                END:VEVENT
                ICAL,
                'Daily Meeting',
                '2025-01-07 10:00:00',
                null, // UTC
                'FREQ=DAILY;COUNT=5',
            ],
            'VEVENT with timezone and weekly RRULE' => [
                <<<ICAL
                BEGIN:VEVENT
                DTSTART;TZID=America/New_York:20250107T150000
                SUMMARY:Weekly Team Meeting
                RRULE:FREQ=WEEKLY;BYDAY=TU;INTERVAL=2
                END:VEVENT
                ICAL,
                'Weekly Team Meeting',
                '2025-01-07 15:00:00',
                'America/New_York',
                'FREQ=WEEKLY;BYDAY=TU;INTERVAL=2',
            ],
            'VEVENT without RRULE' => [
                <<<ICAL
                BEGIN:VEVENT
                DTSTART:20250107T120000Z
                SUMMARY:One-time Event
                END:VEVENT
                ICAL,
                'One-time Event',
                '2025-01-07 12:00:00',
                null,
                null, // No RRULE
            ],
            'VEVENT with complex RRULE' => [
                <<<ICAL
                BEGIN:VEVENT
                DTSTART:20250107T090000Z
                SUMMARY:First Monday Monthly
                RRULE:FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1
                END:VEVENT
                ICAL,
                'First Monday Monthly',
                '2025-01-07 09:00:00',
                null,
                'FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1',
            ],
        ];
    }

    public static function provideVTodoComponents(): array
    {
        return [
            'Simple VTODO with DUE and RRULE' => [
                <<<ICAL
                BEGIN:VTODO
                DUE:20250107T235959Z
                SUMMARY:Monthly Report
                RRULE:FREQ=MONTHLY;BYMONTHDAY=31
                END:VTODO
                ICAL,
                'Monthly Report',
                '2025-01-07 23:59:59',
                null,
                'FREQ=MONTHLY;BYMONTHDAY=31',
            ],
            'VTODO with timezone' => [
                <<<ICAL
                BEGIN:VTODO
                DUE;TZID=Europe/London:20250107T180000
                SUMMARY:Weekly London Task
                RRULE:FREQ=WEEKLY;BYDAY=MO
                END:VTODO
                ICAL,
                'Weekly London Task',
                '2025-01-07 18:00:00',
                'Europe/London',
                'FREQ=WEEKLY;BYDAY=MO',
            ],
            'VTODO without RRULE' => [
                <<<ICAL
                BEGIN:VTODO
                DUE:20250107T120000Z
                SUMMARY:One-time Task
                END:VTODO
                ICAL,
                'One-time Task',
                '2025-01-07 12:00:00',
                null,
                null,
            ],
        ];
    }
}
