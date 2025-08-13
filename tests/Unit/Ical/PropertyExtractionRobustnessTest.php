<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\IcalParser;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive property extraction robustness tests.
 *
 * This test consolidates property extraction edge case testing from multiple
 * specific test methods into comprehensive behavioral validation that focuses
 * on robustness and error handling.
 *
 * Consolidates scenarios from PropertyExtractionEdgeCasesTest methods:
 * - testMissingDtstartInVevent
 * - testMissingDueInVtodoWithDtstart
 * - testMissingBothDtstartAndDueInVtodo
 * - testMalformedDtstartValues
 * - testEmptyPropertyValues
 * - testDuplicateProperties
 * - testPropertyRecoveryFromMalformedData
 */
final class PropertyExtractionRobustnessTest extends TestCase
{
    private IcalParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
    }

    /**
     * Test comprehensive property extraction robustness.
     *
     * This test validates that property extraction handles edge cases gracefully
     * including missing properties, malformed values, empty properties, and
     * duplicate property handling.
     */
    public function testPropertyExtractionRobustnessValidation(): void
    {
        $robustnessScenarios = [
            [
                'description' => 'Missing required DTSTART in VEVENT',
                'icalData' => $this->buildIcalData('VEVENT', [
                    'UID:missing-dtstart@test.example.com',
                    'DTEND:20250101T100000Z',
                    'RRULE:FREQ=DAILY;COUNT=5',
                    'SUMMARY:Event without DTSTART',
                ]),
                'expectedCount' => 0,
                'expectation' => 'VEVENT without DTSTART should be excluded',
            ],
            [
                'description' => 'Missing DUE in VTODO with DTSTART fallback',
                'icalData' => $this->buildIcalData('VTODO', [
                    'UID:missing-due@test.example.com',
                    'DTSTART:20250101T090000Z',
                    'RRULE:FREQ=WEEKLY;COUNT=4',
                    'SUMMARY:Todo with DTSTART but no DUE',
                ]),
                'expectedCount' => 1,
                'expectation' => 'VTODO should use DTSTART as fallback',
            ],
            [
                'description' => 'Missing both DTSTART and DUE in VTODO',
                'icalData' => $this->buildIcalData('VTODO', [
                    'UID:missing-both@test.example.com',
                    'RRULE:FREQ=MONTHLY;COUNT=3',
                    'SUMMARY:Todo without DTSTART or DUE',
                ]),
                'expectedCount' => 0,
                'expectation' => 'VTODO without DTSTART or DUE should be excluded',
            ],
            [
                'description' => 'Empty property values handling',
                'icalData' => $this->buildIcalData('VEVENT', [
                    'UID:empty-values-test@test.example.com',
                    'DTSTART:20250101T090000Z',
                    'DTEND:20250101T100000Z',
                    'RRULE:FREQ=DAILY;COUNT=3',
                    'SUMMARY:',
                    'DESCRIPTION:',
                ]),
                'expectedCount' => 1,
                'expectation' => 'Should handle empty property values gracefully',
            ],
            [
                'description' => 'Duplicate properties handling',
                'icalData' => $this->buildIcalData('VEVENT', [
                    'UID:duplicate-props-test@test.example.com',
                    'DTSTART:20250101T090000Z',
                    'DTSTART:20250101T100000Z',
                    'DTEND:20250101T110000Z',
                    'RRULE:FREQ=DAILY;COUNT=3',
                    'SUMMARY:First Summary',
                    'SUMMARY:Second Summary',
                ]),
                'expectedCount' => 1,
                'expectation' => 'Should handle duplicate properties gracefully',
            ],
            [
                'description' => 'Malformed DTSTART values are rejected',
                'icalData' => $this->buildIcalData('VEVENT', [
                    'UID:malformed-dtstart@test.example.com',
                    'DTSTART:invalid-date-value',
                    'DTEND:20250101T100000Z',
                    'RRULE:FREQ=DAILY;COUNT=5',
                    'SUMMARY:Event with Invalid DTSTART',
                ]),
                'expectedCount' => 0,
                'expectation' => 'Should exclude events with invalid DTSTART',
            ],
            [
                'description' => 'Property recovery from malformed data',
                'icalData' => $this->buildIcalDataWithMalformedLines('VEVENT', [
                    'UID:recovery-test@test.example.com',
                    'DTSTART:20250101T090000Z',
                    'INVALID-LINE-WITHOUT-COLON',
                    'DTEND:20250101T100000Z',
                    'MALFORMED:PROPERTY:WITH:TOO:MANY:COLONS',
                    'RRULE:FREQ=DAILY;COUNT=3',
                    'SUMMARY:Event with Malformed Lines',
                    'ANOTHER-INVALID-LINE',
                ]),
                'expectedCount' => 1,
                'expectation' => 'Should recover valid properties despite malformed lines',
            ],
        ];

        foreach ($robustnessScenarios as $scenario) {
            $results = $this->parser->parse($scenario['icalData']);

            $this->assertCount(
                $scenario['expectedCount'],
                $results,
                "Property extraction robustness scenario '{$scenario['description']}': {$scenario['expectation']}"
            );

            // Additional validation for successful parsing scenarios
            if ($scenario['expectedCount'] > 0) {
                $item = $results[0];
                $this->assertArrayHasKey('dateTimeContext', $item,
                    "Property extraction robustness scenario '{$scenario['description']}': Should have dateTimeContext");
                $this->assertArrayHasKey('rrule', $item,
                    "Property extraction robustness scenario '{$scenario['description']}': Should have rrule");
            }
        }
    }

    /**
     * Test complex property parameters and timezone handling.
     */
    public function testComplexPropertyParametersRobustness(): void
    {
        $complexScenarios = [
            [
                'description' => 'Complex property parameters with timezone',
                'icalData' => $this->buildComplexIcalDataWithTimezone(),
                'expectedCount' => 2,
                'expectation' => 'Should handle complex property parameters',
            ],
            [
                'description' => 'Property value encoding edge cases',
                'icalData' => $this->buildEncodedPropertyIcalData(),
                'expectedCount' => 2,
                'expectation' => 'Should handle property encoding parameters',
            ],
        ];

        foreach ($complexScenarios as $scenario) {
            $results = $this->parser->parse($scenario['icalData']);

            $this->assertCount(
                $scenario['expectedCount'],
                $results,
                "Complex property scenario '{$scenario['description']}': {$scenario['expectation']}"
            );

            foreach ($results as $item) {
                $this->assertArrayHasKey('dateTimeContext', $item,
                    "Complex property scenario '{$scenario['description']}': Should have dateTimeContext");

                $dateTime = $item['dateTimeContext'];
                $this->assertNotNull($dateTime->getDateTime(),
                    "Complex property scenario '{$scenario['description']}': Should have valid parsed datetime");
            }
        }
    }

    /**
     * Test line folding and comprehensive error handling.
     */
    public function testLineFoldingAndErrorHandlingRobustness(): void
    {
        $lineFoldingData = $this->buildLineFoldedIcalData();
        $results = $this->parser->parse($lineFoldingData);

        $this->assertCount(1, $results, 'Should handle line-folded properties');

        $item = $results[0];
        $summary = $item['component']->getProperty('SUMMARY');
        $description = $item['component']->getProperty('DESCRIPTION');

        // Should properly unfold multi-line properties
        $this->assertNotNull($summary, 'Should have SUMMARY property after line unfolding');
        $this->assertNotNull($description, 'Should have DESCRIPTION property after line unfolding');
        $this->assertStringContainsString('compliance', $summary->getValue(),
            'Should properly unfold multi-line SUMMARY content');
        $this->assertStringContainsString('content', $description->getValue(),
            'Should properly unfold multi-line DESCRIPTION content');
    }

    /**
     * Test mixed valid and invalid components.
     */
    public function testMixedValidInvalidComponentHandling(): void
    {
        $mixedData = $this->buildMixedValidInvalidIcalData();
        $results = $this->parser->parse($mixedData);

        $this->assertCount(1, $results, 'Should include only valid components and exclude invalid ones');
        $this->assertEquals('good-event@test.example.com',
            $results[0]['component']->getProperty('UID')->getValue(),
            'Should preserve the valid component');
    }

    /**
     * Helper method to build basic iCalendar data for testing.
     */
    private function buildIcalData(string $componentType, array $properties): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Test Suite//Property Robustness//EN',
            '',
            "BEGIN:{$componentType}",
        ];

        $lines = array_merge($lines, $properties);

        $lines[] = "END:{$componentType}";
        $lines[] = '';
        $lines[] = 'END:VCALENDAR';

        return implode("\n", $lines);
    }

    /**
     * Helper method to build iCalendar data with malformed lines for testing recovery.
     */
    private function buildIcalDataWithMalformedLines(string $componentType, array $properties): string
    {
        return $this->buildIcalData($componentType, $properties);
    }

    /**
     * Helper method to build complex iCalendar data with timezone information.
     */
    private function buildComplexIcalDataWithTimezone(): string
    {
        return <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Robustness//EN
        
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
    }

    /**
     * Helper method to build iCalendar data with property encoding parameters.
     */
    private function buildEncodedPropertyIcalData(): string
    {
        return <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Robustness//EN
        
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
    }

    /**
     * Helper method to build line-folded iCalendar data.
     */
    private function buildLineFoldedIcalData(): string
    {
        return "BEGIN:VCALENDAR\r\n".
               "VERSION:2.0\r\n".
               "PRODID:-//Test Suite//Property Robustness//EN\r\n".
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
    }

    /**
     * Helper method to build mixed valid/invalid iCalendar data.
     */
    private function buildMixedValidInvalidIcalData(): string
    {
        return <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Property Robustness//EN
        
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
    }
}
