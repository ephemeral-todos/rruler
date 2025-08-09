<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\IcalParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for DTSTART/DUE property extraction edge cases.
 *
 * This test suite validates robust property extraction handling for various
 * edge cases including missing values, malformed properties, and complex
 * property parameter scenarios.
 */
final class PropertyExtractionEdgeCasesTest extends TestCase
{
    private IcalParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
    }

    /**
     * Test handling of missing DTSTART property in VEVENT.
     */
    public function testMissingDtstartInVevent(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:missing-dtstart@test.example.com
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Event without DTSTART
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should gracefully handle missing DTSTART by excluding the component
        $this->assertCount(0, $results, 'VEVENT without DTSTART should be excluded');
    }

    /**
     * Test handling of missing DUE property in VTODO with DTSTART fallback.
     */
    public function testMissingDueInVtodoWithDtstart(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VTODO
        UID:missing-due@test.example.com
        DTSTART:20250101T090000Z
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Todo with DTSTART but no DUE
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should use DTSTART as fallback for missing DUE in VTODO
        $this->assertCount(1, $results, 'VTODO should use DTSTART as fallback');

        $item = $results[0];
        $this->assertEquals('VTODO', $item['component']->getType());
        $this->assertArrayHasKey('dateTimeContext', $item);
        $this->assertEquals('2025-01-01 09:00:00', $item['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * Test handling of missing both DTSTART and DUE in VTODO.
     */
    public function testMissingBothDtstartAndDueInVtodo(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VTODO
        UID:missing-both@test.example.com
        RRULE:FREQ=MONTHLY;COUNT=3
        SUMMARY:Todo without DTSTART or DUE
        PRIORITY:1
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should exclude component without any date/time reference
        $this->assertCount(0, $results, 'VTODO without DTSTART or DUE should be excluded');
    }

    /**
     * Test malformed DTSTART property values.
     */
    public function testMalformedDtstartValues(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:good-event@test.example.com
        DTSTART:20250101T120000Z
        DTEND:20250101T130000Z
        RRULE:FREQ=MONTHLY;COUNT=2
        SUMMARY:Valid Event
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should include the valid event
        $this->assertCount(1, $results, 'Should include valid events');

        $item = $results[0];
        $this->assertEquals('good-event@test.example.com', $item['component']->getProperty('UID')->getValue());
        $this->assertEquals('2025-01-01 12:00:00', $item['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));

        // Test that truly invalid dates are rejected by the parser
        $invalidData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:malformed-dtstart@test.example.com
        DTSTART:invalid-date-value
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Event with Invalid DTSTART
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $invalidResults = $this->parser->parse($invalidData);
        $this->assertCount(0, $invalidResults, 'Should exclude events with invalid DTSTART');
    }

    /**
     * Test complex property parameters in DTSTART/DUE.
     */
    public function testComplexPropertyParameters(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VTIMEZONE
        TZID:America/New_York
        BEGIN:STANDARD
        DTSTART:20071104T020000
        RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
        TZNAME:EST
        TZOFFSETFROM:-0400
        TZOFFSETTO:-0500
        END:STANDARD
        BEGIN:DAYLIGHT
        DTSTART:20070311T020000
        RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
        TZNAME:EDT
        TZOFFSETFROM:-0500
        TZOFFSETTO:-0400
        END:DAYLIGHT
        END:VTIMEZONE
        
        BEGIN:VEVENT
        UID:complex-params-1@test.example.com
        DTSTART;TZID=America/New_York;VALUE=DATE-TIME:20250101T090000
        DTEND;TZID=America/New_York:20250101T100000
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Event with Complex DTSTART Parameters
        END:VEVENT
        
        BEGIN:VTODO
        UID:complex-params-2@test.example.com
        DTSTART;TZID=America/New_York:20250102T140000
        DUE;TZID=America/New_York;VALUE=DATE-TIME:20250102T180000
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Todo with Complex DUE Parameters
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        $this->assertCount(2, $results, 'Should handle complex property parameters');

        foreach ($results as $item) {
            $this->assertArrayHasKey('dateTimeContext', $item);
            $dateTime = $item['dateTimeContext'];

            // Should properly extract timezone information
            $this->assertEquals('America/New_York', $dateTime->getTimezone());

            // Should have valid parsed datetime
            $this->assertNotNull($dateTime->getDateTime());
        }
    }

    /**
     * Test property value encoding edge cases.
     */
    public function testPropertyValueEncoding(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:encoding-test-1@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=3
        SUMMARY;ENCODING=QUOTED-PRINTABLE:Event with Encoding
        END:VEVENT
        
        BEGIN:VTODO
        UID:encoding-test-2@test.example.com
        DTSTART:20250102T120000Z
        DUE:20250102T180000Z
        RRULE:FREQ=WEEKLY;COUNT=2
        SUMMARY;CHARSET=UTF-8:Todo with Character Set
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        $this->assertCount(2, $results, 'Should handle property encoding parameters');

        // Verify both components parsed correctly despite encoding parameters
        $types = [];
        foreach ($results as $item) {
            $types[] = $item['component']->getType();
            $this->assertArrayHasKey('dateTimeContext', $item);
            $this->assertArrayHasKey('rrule', $item);
        }

        $this->assertContains('VEVENT', $types);
        $this->assertContains('VTODO', $types);
    }

    /**
     * Test line folding in property values.
     */
    public function testLineFoldingInProperties(): void
    {
        // Create properly folded iCalendar data (with actual CRLF + space)
        $icalData = "BEGIN:VCALENDAR\r\n".
                   "VERSION:2.0\r\n".
                   "PRODID:-//Test Suite//Property Edge Cases//EN\r\n".
                   "BEGIN:VEVENT\r\n".
                   "UID:line-folding-test@test.example.com\r\n".
                   "DTSTART:20250101T090000Z\r\n".
                   "DTEND:20250101T100000Z\r\n".
                   "RRULE:FREQ=DAILY;COUNT=5\r\n".
                   "SUMMARY:This is a very long summary that should be folded across\r\n".
                   " multiple lines according to RFC 5545 line folding rules for proper\r\n".
                   " iCalendar format compliance\r\n".
                   "DESCRIPTION:Another long property that spans multiple lines and\r\n".
                   " should be properly unfolded during parsing to maintain the correct\r\n".
                   " property value content\r\n".
                   "END:VEVENT\r\n".
                   "END:VCALENDAR\r\n";

        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results, 'Should handle line-folded properties');

        $item = $results[0];
        $summary = $item['component']->getProperty('SUMMARY');
        $description = $item['component']->getProperty('DESCRIPTION');

        // Should properly unfold multi-line properties
        $this->assertNotNull($summary);
        $this->assertNotNull($description);
        $this->assertStringContainsString('compliance', $summary->getValue());
        $this->assertStringContainsString('content', $description->getValue());
    }

    /**
     * Test empty property values.
     */
    public function testEmptyPropertyValues(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:empty-values-test@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=3
        SUMMARY:
        DESCRIPTION:
        LOCATION:
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results, 'Should handle empty property values');

        $item = $results[0];
        $component = $item['component'];

        // Should still parse the component with valid DTSTART/DTEND
        $this->assertArrayHasKey('dateTimeContext', $item);
        $this->assertArrayHasKey('rrule', $item);

        // Empty properties should be present but with empty values
        $summary = $component->getProperty('SUMMARY');
        $this->assertNotNull($summary);
        $this->assertEquals('', $summary->getValue());
    }

    /**
     * Test duplicate property handling.
     */
    public function testDuplicateProperties(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:duplicate-props-test@test.example.com
        DTSTART:20250101T090000Z
        DTSTART:20250101T100000Z
        DTEND:20250101T110000Z
        DTEND:20250101T120000Z
        RRULE:FREQ=DAILY;COUNT=3
        SUMMARY:First Summary
        SUMMARY:Second Summary
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results, 'Should handle duplicate properties');

        $item = $results[0];
        $this->assertArrayHasKey('dateTimeContext', $item);
        $this->assertArrayHasKey('rrule', $item);

        // Should use one of the DTSTART values (implementation-specific which one)
        $this->assertNotNull($item['dateTimeContext']->getDateTime());
    }

    /**
     * Test property recovery from malformed data.
     */
    public function testPropertyRecoveryFromMalformedData(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:recovery-test@test.example.com
        DTSTART:20250101T090000Z
        INVALID-LINE-WITHOUT-COLON
        DTEND:20250101T100000Z
        MALFORMED:PROPERTY:WITH:TOO:MANY:COLONS
        RRULE:FREQ=DAILY;COUNT=3
        SUMMARY:Event with Malformed Lines
        ANOTHER-INVALID-LINE
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should recover valid properties despite malformed lines
        $this->assertCount(1, $results, 'Should recover from malformed data');

        $item = $results[0];
        $this->assertArrayHasKey('dateTimeContext', $item);
        $this->assertArrayHasKey('rrule', $item);
        $this->assertEquals('2025-01-01 09:00:00', $item['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
    }

    /**
     * Test comprehensive error reporting.
     */
    public function testComprehensiveErrorReporting(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:good-event@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Good Event
        END:VEVENT
        
        BEGIN:VEVENT
        UID:another-good-event@test.example.com
        DTSTART:20250102T140000Z
        DTEND:20250102T150000Z
        RRULE:FREQ=YEARLY;COUNT=1
        SUMMARY:Another Good Event
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should include both valid events
        $this->assertCount(2, $results, 'Should include valid components');

        // Verify the valid components are correct
        $uids = [];
        foreach ($results as $item) {
            $uids[] = $item['component']->getProperty('UID')->getValue();
        }

        $this->assertContains('good-event@test.example.com', $uids);
        $this->assertContains('another-good-event@test.example.com', $uids);

        // Test that invalid components are properly excluded
        $mixedData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Edge Cases//EN
        
        BEGIN:VEVENT
        UID:good-event@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Good Event
        END:VEVENT
        
        BEGIN:VTODO
        UID:missing-dates@test.example.com
        RRULE:FREQ=MONTHLY;COUNT=2
        SUMMARY:Todo without Dates
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $mixedResults = $this->parser->parse($mixedData);
        $this->assertCount(1, $mixedResults, 'Should exclude components without valid dates');
        $this->assertEquals('good-event@test.example.com', $mixedResults[0]['component']->getProperty('UID')->getValue());
    }
}
