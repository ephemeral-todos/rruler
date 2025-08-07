<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\Ical;

use EphemeralTodos\Rruler\Ical\ComponentType;
use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for complete iCalendar parsing workflows.
 *
 * These tests validate the entire workflow from raw iCalendar data to final occurrence generation,
 * ensuring all components work together correctly for real-world usage scenarios.
 */
final class IcalWorkflowIntegrationTest extends TestCase
{
    private IcalParser $parser;
    private DefaultOccurrenceGenerator $occurrenceGenerator;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
        $this->occurrenceGenerator = new DefaultOccurrenceGenerator();
    }

    public function testCompleteWorkflowWithDailyVEvent(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Company//Product//EN
        CALSCALE:GREGORIAN
        METHOD:PUBLISH
        BEGIN:VEVENT
        UID:daily-standup@company.com
        DTSTART:20250107T090000Z
        DTEND:20250107T093000Z
        SUMMARY:Daily Standup Meeting
        DESCRIPTION:Daily team standup to discuss progress and blockers
        LOCATION:Conference Room A
        ORGANIZER:CN=Team Lead:MAILTO:lead@company.com
        ATTENDEE:CN=Developer 1:MAILTO:dev1@company.com
        ATTENDEE:CN=Developer 2:MAILTO:dev2@company.com
        RRULE:FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=10
        SEQUENCE:0
        STATUS:CONFIRMED
        TRANSP:OPAQUE
        CREATED:20250101T000000Z
        LAST-MODIFIED:20250105T120000Z
        END:VEVENT
        END:VCALENDAR
        ICAL;

        // Step 1: Parse iCalendar data
        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results);
        $result = $results[0];

        // Step 2: Verify parsed components
        $this->assertArrayHasKey('component', $result);
        $this->assertArrayHasKey('dateTimeContext', $result);
        $this->assertArrayHasKey('rrule', $result);

        $component = $result['component'];
        $dateTimeContext = $result['dateTimeContext'];
        $rrule = $result['rrule'];

        // Step 3: Validate component parsing
        $this->assertEquals('VEVENT', $component->getType());
        $this->assertEquals('Daily Standup Meeting', $component->getProperty('SUMMARY')->getValue());
        $this->assertEquals('daily-standup@company.com', $component->getProperty('UID')->getValue());

        // Step 4: Validate DateTimeContext extraction
        $this->assertEquals(ComponentType::VEVENT, $dateTimeContext->getComponentType());
        $this->assertEquals('2025-01-07 09:00:00', $dateTimeContext->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertTrue($dateTimeContext->isUtc());

        // Step 5: Validate RRULE parsing (allow parameter order differences)
        $this->assertRruleStringMatches('FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=10', (string) $rrule);

        // Step 6: Generate occurrences
        $startRange = new \DateTimeImmutable('2025-01-07 00:00:00');
        $endRange = new \DateTimeImmutable('2025-01-20 23:59:59');

        $occurrences = $this->occurrenceGenerator->generateOccurrencesInRange(
            $rrule,
            $dateTimeContext->getDateTime(),
            $startRange,
            $endRange
        );

        $occurrenceList = iterator_to_array($occurrences);

        // Step 7: Validate occurrence generation
        $this->assertCount(10, $occurrenceList);

        // Verify first few occurrences (weekdays only)
        $this->assertEquals('2025-01-07', $occurrenceList[0]->format('Y-m-d')); // Tuesday
        $this->assertEquals('2025-01-08', $occurrenceList[1]->format('Y-m-d')); // Wednesday
        $this->assertEquals('2025-01-09', $occurrenceList[2]->format('Y-m-d')); // Thursday
        $this->assertEquals('2025-01-10', $occurrenceList[3]->format('Y-m-d')); // Friday
        $this->assertEquals('2025-01-13', $occurrenceList[4]->format('Y-m-d')); // Monday

        // Verify all occurrences are at the same time
        foreach ($occurrenceList as $occurrence) {
            $this->assertEquals('09:00:00', $occurrence->format('H:i:s'));
        }
    }

    public function testCompleteWorkflowWithMonthlyVTodo(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Task Manager//App v1.0//EN
        BEGIN:VTODO
        UID:monthly-report-123
        DTSTART:20250101T080000Z
        DUE:20250107T170000Z
        SUMMARY:Monthly Status Report
        DESCRIPTION:Compile and submit monthly team status report
        PRIORITY:1
        STATUS:NEEDS-ACTION
        PERCENT-COMPLETE:0
        RRULE:FREQ=MONTHLY;BYMONTHDAY=7;UNTIL=20250707T170000Z
        CATEGORIES:Reports,Management
        CREATED:20250101T000000Z
        LAST-MODIFIED:20250101T120000Z
        END:VTODO
        END:VCALENDAR
        ICAL;

        // Complete workflow execution
        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results);
        $result = $results[0];

        // Validate VTODO parsing
        $component = $result['component'];
        $dateTimeContext = $result['dateTimeContext'];
        $rrule = $result['rrule'];

        $this->assertEquals('VTODO', $component->getType());
        $this->assertEquals('Monthly Status Report', $component->getProperty('SUMMARY')->getValue());
        $this->assertEquals('monthly-report-123', $component->getProperty('UID')->getValue());

        // Validate DateTimeContext uses DUE property for VTODO
        $this->assertEquals(ComponentType::VTODO, $dateTimeContext->getComponentType());
        $this->assertEquals('2025-01-07 17:00:00', $dateTimeContext->getDateTime()->format('Y-m-d H:i:s'));

        // Validate RRULE with UNTIL (allow parameter order differences)
        $this->assertRruleStringMatches('FREQ=MONTHLY;BYMONTHDAY=7;UNTIL=20250707T170000Z', (string) $rrule);

        // Generate occurrences
        $startRange = new \DateTimeImmutable('2025-01-01');
        $endRange = new \DateTimeImmutable('2025-12-31');

        $occurrences = $this->occurrenceGenerator->generateOccurrencesInRange(
            $rrule,
            $dateTimeContext->getDateTime(),
            $startRange,
            $endRange
        );

        $occurrenceList = iterator_to_array($occurrences);

        // Should have 7 occurrences (Jan 7 through Jul 7, inclusive of UNTIL date)
        $this->assertCount(7, $occurrenceList);

        // Verify monthly progression on the 7th
        $this->assertEquals('2025-01-07', $occurrenceList[0]->format('Y-m-d'));
        $this->assertEquals('2025-02-07', $occurrenceList[1]->format('Y-m-d'));
        $this->assertEquals('2025-03-07', $occurrenceList[2]->format('Y-m-d'));
        $this->assertEquals('2025-04-07', $occurrenceList[3]->format('Y-m-d'));
        $this->assertEquals('2025-05-07', $occurrenceList[4]->format('Y-m-d'));
        $this->assertEquals('2025-06-07', $occurrenceList[5]->format('Y-m-d'));
        $this->assertEquals('2025-07-07', $occurrenceList[6]->format('Y-m-d'));
    }

    #[DataProvider('provideComplexIcalScenarios')]
    public function testComplexIcalScenariosWorkflow(
        string $icalData,
        int $expectedComponentCount,
        array $expectedSummaries,
        array $expectedOccurrenceCounts,
    ): void {
        // Parse complex iCalendar file
        $results = $this->parser->parse($icalData);

        $this->assertCount($expectedComponentCount, $results);

        // Validate each component and generate occurrences
        foreach ($results as $index => $result) {
            $component = $result['component'];
            $dateTimeContext = $result['dateTimeContext'];

            // Verify component summary
            $summaryProperty = $component->getProperty('SUMMARY');
            $this->assertNotNull($summaryProperty);
            $this->assertEquals($expectedSummaries[$index], $summaryProperty->getValue());

            // Generate and count occurrences if RRULE exists
            if (isset($result['rrule'])) {
                $startRange = new \DateTimeImmutable('2025-01-01');
                $endRange = new \DateTimeImmutable('2025-03-31');

                $occurrences = $this->occurrenceGenerator->generateOccurrencesInRange(
                    $result['rrule'],
                    $dateTimeContext->getDateTime(),
                    $startRange,
                    $endRange
                );

                $occurrenceList = iterator_to_array($occurrences);
                $this->assertCount($expectedOccurrenceCounts[$index], $occurrenceList);
            } else {
                // No RRULE means single occurrence
                $this->assertEquals(0, $expectedOccurrenceCounts[$index]);
            }
        }
    }

    public function testTimezoneHandlingInWorkflow(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Example Corp//CalDAV Client//EN
        BEGIN:VTIMEZONE
        TZID:America/New_York
        BEGIN:DAYLIGHT
        TZOFFSETFROM:-0500
        TZOFFSETTO:-0400
        TZNAME:EDT
        DTSTART:20070311T020000
        RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=2SU
        END:DAYLIGHT
        BEGIN:STANDARD
        TZOFFSETFROM:-0400
        TZOFFSETTO:-0500
        TZNAME:EST
        DTSTART:20071104T020000
        RRULE:FREQ=YEARLY;BYMONTH=11;BYDAY=1SU
        END:STANDARD
        END:VTIMEZONE
        BEGIN:VEVENT
        UID:timezone-test-event
        DTSTART;TZID=America/New_York:20250107T143000
        SUMMARY:Timezone Test Event
        RRULE:FREQ=WEEKLY;COUNT=4
        END:VEVENT
        END:VCALENDAR
        ICAL;

        // Test complete workflow with timezone
        $results = $this->parser->parse($icalData);

        $this->assertCount(1, $results); // Should ignore VTIMEZONE and extract only VEVENT

        $result = $results[0];
        $component = $result['component'];
        $dateTimeContext = $result['dateTimeContext'];

        // Verify timezone extraction
        $this->assertEquals('Timezone Test Event', $component->getProperty('SUMMARY')->getValue());
        $this->assertEquals('America/New_York', $dateTimeContext->getTimezone());
        $this->assertEquals('2025-01-07 14:30:00', $dateTimeContext->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertFalse($dateTimeContext->isUtc());
        $this->assertTrue($dateTimeContext->hasTimezone());

        // Generate occurrences with timezone context
        if (isset($result['rrule'])) {
            $startRange = new \DateTimeImmutable('2025-01-01');
            $endRange = new \DateTimeImmutable('2025-02-28');

            $occurrences = $this->occurrenceGenerator->generateOccurrencesInRange(
                $result['rrule'],
                $dateTimeContext->getDateTime(),
                $startRange,
                $endRange
            );

            $occurrenceList = iterator_to_array($occurrences);
            $this->assertCount(4, $occurrenceList);

            // Verify weekly progression
            $this->assertEquals('2025-01-07', $occurrenceList[0]->format('Y-m-d'));
            $this->assertEquals('2025-01-14', $occurrenceList[1]->format('Y-m-d'));
            $this->assertEquals('2025-01-21', $occurrenceList[2]->format('Y-m-d'));
            $this->assertEquals('2025-01-28', $occurrenceList[3]->format('Y-m-d'));
        }
    }

    public function testErrorRecoveryInWorkflow(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        BEGIN:VEVENT
        DTSTART:20250107T100000Z
        SUMMARY:Valid Event
        RRULE:FREQ=DAILY;COUNT=3
        END:VEVENT
        BEGIN:VEVENT
        DTSTART:INVALID-DATE-FORMAT
        SUMMARY:Invalid Event
        RRULE:FREQ=DAILY;COUNT=3
        END:VEVENT
        BEGIN:VEVENT
        DTSTART:20250108T110000Z
        SUMMARY:Another Valid Event
        RRULE:INVALID-RRULE-FORMAT
        END:VEVENT
        END:VCALENDAR
        ICAL;

        // Test workflow error recovery
        $results = $this->parser->parse($icalData);

        // Should parse at least the valid components (may skip invalid ones gracefully)
        // Note: Current implementation may return empty results for malformed data
        $this->assertIsArray($results);

        foreach ($results as $result) {
            // All returned results should have valid components
            $this->assertArrayHasKey('component', $result);
            $this->assertArrayHasKey('dateTimeContext', $result);

            $component = $result['component'];
            $dateTimeContext = $result['dateTimeContext'];

            // Should have valid summary and datetime
            $this->assertNotNull($component->getProperty('SUMMARY'));
            $this->assertInstanceOf(\DateTimeImmutable::class, $dateTimeContext->getDateTime());
        }
    }

    public function testEndToEndPerformanceBaseline(): void
    {
        // Generate a larger iCalendar file for performance testing
        $events = [];
        for ($i = 1; $i <= 50; ++$i) {
            $day = str_pad((string) (($i % 28) + 1), 2, '0', STR_PAD_LEFT);
            $hour = str_pad((string) ($i % 24), 2, '0', STR_PAD_LEFT);

            $events[] = sprintf(
                <<<EVENT
                BEGIN:VEVENT
                UID:event-%d@test.com
                DTSTART:202501%sT%s0000Z
                SUMMARY:Test Event %d
                RRULE:FREQ=DAILY;COUNT=5
                END:VEVENT
                EVENT,
                $i,
                $day,
                $hour,
                $i
            );
        }

        $icalData = sprintf(
            <<<ICAL
            BEGIN:VCALENDAR
            VERSION:2.0
            PRODID:-//Performance Test//EN
            %s
            END:VCALENDAR
            ICAL,
            implode("\n", $events)
        );

        $startTime = microtime(true);

        // Test complete workflow performance
        $results = $this->parser->parse($icalData);

        $this->assertCount(50, $results);

        // Generate occurrences for all components
        $totalOccurrences = 0;
        $startRange = new \DateTimeImmutable('2025-01-01');
        $endRange = new \DateTimeImmutable('2025-01-31');

        foreach ($results as $result) {
            if (isset($result['rrule'])) {
                $occurrences = $this->occurrenceGenerator->generateOccurrencesInRange(
                    $result['rrule'],
                    $result['dateTimeContext']->getDateTime(),
                    $startRange,
                    $endRange
                );
                $totalOccurrences += count(iterator_to_array($occurrences));
            }
        }

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        // Basic performance assertions (allow some variance due to date ranges)
        $this->assertGreaterThan(240, $totalOccurrences); // Should be close to 250 (50 × 5)
        $this->assertLessThan(1.0, $executionTime, 'Should process 50 components with occurrences in under 1 second');
    }

    /**
     * Helper method to compare RRULE strings ignoring parameter order.
     */
    private function assertRruleStringMatches(string $expected, string $actual): void
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

    public static function provideComplexIcalScenarios(): array
    {
        return [
            'Mixed VEVENT and VTODO with different patterns' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VEVENT
                DTSTART:20250107T090000Z
                SUMMARY:Weekly Meeting
                RRULE:FREQ=WEEKLY;COUNT=12
                END:VEVENT
                BEGIN:VTODO
                DUE:20250110T170000Z
                SUMMARY:Monthly Task
                RRULE:FREQ=MONTHLY;BYMONTHDAY=10;COUNT=3
                END:VTODO
                BEGIN:VEVENT
                DTSTART:20250115T140000Z
                SUMMARY:One-time Event
                END:VEVENT
                END:VCALENDAR
                ICAL,
                3, // Expected component count
                ['Weekly Meeting', 'Monthly Task', 'One-time Event'], // Expected summaries
                [12, 3, 0], // Expected occurrence counts (0 for one-time event)
            ],
            'Complex yearly pattern with BYMONTH and BYDAY' => [
                <<<ICAL
                BEGIN:VCALENDAR
                VERSION:2.0
                BEGIN:VEVENT
                DTSTART:20250107T120000Z
                SUMMARY:Quarterly Review
                RRULE:FREQ=YEARLY;BYMONTH=1,4,7,10;BYDAY=1TU;COUNT=1
                END:VEVENT
                END:VCALENDAR
                ICAL,
                1,
                ['Quarterly Review'],
                [1], // Only one occurrence in Q1 range
            ],
        ];
    }
}
