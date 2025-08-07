<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\Ical;

use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Reader;

/**
 * Compatibility tests between Rruler and sabre/vobject for iCalendar parsing and RRULE processing.
 *
 * These tests ensure that Rruler produces identical results to sabre/vobject when processing
 * the same iCalendar data, validating RFC 5545 compliance and compatibility.
 */
final class SabreCompatibilityTest extends TestCase
{
    private IcalParser $rrulerParser;
    private DefaultOccurrenceGenerator $rrulerGenerator;

    protected function setUp(): void
    {
        $this->rrulerParser = new IcalParser();
        $this->rrulerGenerator = new DefaultOccurrenceGenerator();
    }

    #[DataProvider('provideBasicVEventData')]
    public function testRrulerMatchesSabreForBasicVEvents(
        string $icalData,
        string $expectedSummary,
        string $occurrenceRangeStart,
        string $occurrenceRangeEnd,
        int $expectedOccurrenceCount,
    ): void {
        // Parse with Rruler
        $rrulerResults = $this->rrulerParser->parse($icalData);
        $this->assertCount(1, $rrulerResults, 'Should parse exactly one component');

        $rrulerResult = $rrulerResults[0];
        $rrulerComponent = $rrulerResult['component'];
        $rrulerDateTime = $rrulerResult['dateTimeContext'];

        // Verify basic parsing
        $summaryProperty = $rrulerComponent->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals($expectedSummary, $summaryProperty->getValue());

        // Parse with sabre/vobject
        $sabreCalendar = Reader::read($icalData);
        $this->assertInstanceOf(VCalendar::class, $sabreCalendar);

        $sabreEvents = $sabreCalendar->select('VEVENT');
        $this->assertCount(1, $sabreEvents, 'Should find exactly one VEVENT');

        $sabreEvent = $sabreEvents[0];
        $this->assertEquals($expectedSummary, (string) $sabreEvent->SUMMARY);

        // Compare RRULE strings if RRULE exists
        if (isset($rrulerResult['rrule'])) {
            $this->assertTrue(isset($sabreEvent->RRULE), 'sabre should find RRULE property');

            $sabreRruleString = (string) $sabreEvent->RRULE;
            $rrulerRruleString = (string) $rrulerResult['rrule'];

            // Compare RRULE strings (allowing for parameter order differences)
            $this->assertRruleStringsEquivalent($sabreRruleString, $rrulerRruleString);

            // Test basic occurrence generation count
            $this->assertOccurrenceCountMatches(
                $rrulerResult['rrule'],
                $rrulerDateTime->getDateTime(),
                $occurrenceRangeStart,
                $occurrenceRangeEnd,
                $expectedOccurrenceCount
            );
        }
    }

    #[DataProvider('provideBasicVTodoData')]
    public function testRrulerMatchesSabreForBasicVTodos(
        string $icalData,
        string $expectedSummary,
        string $occurrenceRangeStart,
        string $occurrenceRangeEnd,
        int $expectedOccurrenceCount,
    ): void {
        // Parse with Rruler
        $rrulerResults = $this->rrulerParser->parse($icalData);
        $this->assertCount(1, $rrulerResults);

        $rrulerResult = $rrulerResults[0];
        $rrulerComponent = $rrulerResult['component'];
        $rrulerDateTime = $rrulerResult['dateTimeContext'];

        // Verify basic parsing
        $summaryProperty = $rrulerComponent->getProperty('SUMMARY');
        $this->assertNotNull($summaryProperty);
        $this->assertEquals($expectedSummary, $summaryProperty->getValue());

        // Parse with sabre/vobject
        $sabreCalendar = Reader::read($icalData);
        $sabreTodos = $sabreCalendar->select('VTODO');
        $this->assertCount(1, $sabreTodos);

        $sabreTodo = $sabreTodos[0];
        $this->assertEquals($expectedSummary, (string) $sabreTodo->SUMMARY);

        // Compare RRULE strings if RRULE exists
        if (isset($rrulerResult['rrule'])) {
            $this->assertTrue(isset($sabreTodo->RRULE), 'sabre should find RRULE property');

            $sabreRruleString = (string) $sabreTodo->RRULE;
            $rrulerRruleString = (string) $rrulerResult['rrule'];

            // Compare RRULE strings (allowing for parameter order differences)
            $this->assertRruleStringsEquivalent($sabreRruleString, $rrulerRruleString);

            // Test basic occurrence generation count
            $this->assertOccurrenceCountMatches(
                $rrulerResult['rrule'],
                $rrulerDateTime->getDateTime(),
                $occurrenceRangeStart,
                $occurrenceRangeEnd,
                $expectedOccurrenceCount
            );
        }
    }

    public function testHandlesComplexNestedCalendarStructure(): void
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
        DTSTART;TZID=America/New_York:20250107T100000
        SUMMARY:Weekly Team Meeting
        RRULE:FREQ=WEEKLY;BYDAY=TU;COUNT=4
        END:VEVENT
        BEGIN:VTODO
        DUE;TZID=America/New_York:20250107T180000
        SUMMARY:Monthly Review
        RRULE:FREQ=MONTHLY;BYMONTHDAY=15
        END:VTODO
        END:VCALENDAR
        ICAL;

        // Parse with Rruler - should find both VEVENT and VTODO
        $rrulerResults = $this->rrulerParser->parse($icalData);
        $this->assertCount(2, $rrulerResults);

        // Parse with sabre/vobject
        $sabreCalendar = Reader::read($icalData);
        $sabreEvents = $sabreCalendar->select('VEVENT');
        $sabreTodos = $sabreCalendar->select('VTODO');

        $this->assertCount(1, $sabreEvents);
        $this->assertCount(1, $sabreTodos);

        // Verify both parsers found the same components
        $eventResult = null;
        $todoResult = null;

        foreach ($rrulerResults as $result) {
            if ($result['component']->getType() === 'VEVENT') {
                $eventResult = $result;
            } elseif ($result['component']->getType() === 'VTODO') {
                $todoResult = $result;
            }
        }

        $this->assertNotNull($eventResult);
        $this->assertNotNull($todoResult);

        // Compare VEVENT
        $eventSummary = $eventResult['component']->getProperty('SUMMARY');
        $this->assertEquals('Weekly Team Meeting', $eventSummary->getValue());
        $this->assertEquals('Weekly Team Meeting', (string) $sabreEvents[0]->SUMMARY);

        // Compare VTODO
        $todoSummary = $todoResult['component']->getProperty('SUMMARY');
        $this->assertEquals('Monthly Review', $todoSummary->getValue());
        $this->assertEquals('Monthly Review', (string) $sabreTodos[0]->SUMMARY);
    }

    public function testIgnoresUnsupportedComponents(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        BEGIN:VFREEBUSY
        DTSTART:20250101T000000Z
        DTEND:20250131T235959Z
        END:VFREEBUSY
        BEGIN:VJOURNAL
        DTSTART:20250107T120000Z
        SUMMARY:Daily Journal
        END:VJOURNAL
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:Important Event
        RRULE:FREQ=DAILY;COUNT=3
        END:VEVENT
        END:VCALENDAR
        ICAL;

        // Rruler should only extract VEVENT, ignoring VFREEBUSY and VJOURNAL
        $rrulerResults = $this->rrulerParser->parse($icalData);
        $this->assertCount(1, $rrulerResults);
        $this->assertEquals('VEVENT', $rrulerResults[0]['component']->getType());

        // sabre/vobject should find all components
        $sabreCalendar = Reader::read($icalData);
        $sabreEvents = $sabreCalendar->select('VEVENT');
        $sabreFreebusy = $sabreCalendar->select('VFREEBUSY');
        $sabreJournal = $sabreCalendar->select('VJOURNAL');

        $this->assertCount(1, $sabreEvents);
        $this->assertCount(1, $sabreFreebusy);
        $this->assertCount(1, $sabreJournal);

        // But we only care about the VEVENT for compatibility
        $eventSummary = $rrulerResults[0]['component']->getProperty('SUMMARY');
        $this->assertEquals('Important Event', $eventSummary->getValue());
        $this->assertEquals('Important Event', (string) $sabreEvents[0]->SUMMARY);
    }

    /**
     * Assert that RRULE strings are equivalent (ignoring parameter order).
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

    /**
     * Assert that Rruler generates the expected number of occurrences.
     */
    private function assertOccurrenceCountMatches(
        Rrule $rrule,
        \DateTimeImmutable $dtStart,
        string $rangeStart,
        string $rangeEnd,
        int $expectedCount,
    ): void {
        $rangeStartDate = new \DateTimeImmutable($rangeStart);
        $rangeEndDate = new \DateTimeImmutable($rangeEnd);

        // Generate occurrences with Rruler
        $rrulerOccurrences = $this->rrulerGenerator->generateOccurrencesInRange(
            $rrule,
            $dtStart,
            $rangeStartDate,
            $rangeEndDate
        );
        $rrulerDates = iterator_to_array($rrulerOccurrences);

        // Verify expected count
        $this->assertCount($expectedCount, $rrulerDates, 'Rruler occurrence count should match expected');
    }

    public static function provideBasicVEventData(): array
    {
        return [
            'Daily VEVENT with COUNT' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VEVENT
                DTSTART:20250107T100000Z
                SUMMARY:Daily Standup
                RRULE:FREQ=DAILY;COUNT=5
                END:VEVENT
                END:VCALENDAR
                ICAL,
                'Daily Standup',
                '2025-01-07 10:00:00',
                '2025-01-15 10:00:00',
                5,
            ],
            'Weekly VEVENT with BYDAY' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VEVENT
                DTSTART:20250107T150000Z
                SUMMARY:Team Meeting
                RRULE:FREQ=WEEKLY;BYDAY=TU;COUNT=3
                END:VEVENT
                END:VCALENDAR
                ICAL,
                'Team Meeting',
                '2025-01-07 15:00:00',
                '2025-01-28 15:00:00',
                3,
            ],
            'Monthly VEVENT' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VEVENT
                DTSTART:20250107T120000Z
                SUMMARY:Monthly Review
                RRULE:FREQ=MONTHLY;BYMONTHDAY=7;COUNT=3
                END:VEVENT
                END:VCALENDAR
                ICAL,
                'Monthly Review',
                '2025-01-07 12:00:00',
                '2025-04-07 12:00:00',
                3,
            ],
        ];
    }

    public static function provideBasicVTodoData(): array
    {
        return [
            'Daily VTODO with COUNT' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VTODO
                DUE:20250107T235959Z
                SUMMARY:Daily Task
                RRULE:FREQ=DAILY;COUNT=5
                END:VTODO
                END:VCALENDAR
                ICAL,
                'Daily Task',
                '2025-01-07 23:59:59',
                '2025-01-15 23:59:59',
                5,
            ],
            'Weekly VTODO with BYDAY' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VTODO
                DUE:20250107T180000Z
                SUMMARY:Weekly Report
                RRULE:FREQ=WEEKLY;BYDAY=TU;COUNT=4
                END:VTODO
                END:VCALENDAR
                ICAL,
                'Weekly Report',
                '2025-01-07 18:00:00',
                '2025-01-28 18:00:00',
                4,
            ],
        ];
    }
}
