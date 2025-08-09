<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\IcalParser;
use PHPUnit\Framework\TestCase;

/**
 * Tests for multi-component VCALENDAR file processing scenarios.
 *
 * This test suite validates the parser's ability to handle complex iCalendar files
 * with 10+ mixed components efficiently, including proper component ordering,
 * filtering, and memory-efficient processing.
 */
final class MultiComponentVcalendarTest extends TestCase
{
    private IcalParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
    }

    /**
     * Test parsing files with 10+ mixed components.
     */
    public function testTenPlusMixedComponents(): void
    {
        $icalData = $this->createLargeMultiComponentCalendar(15);

        $results = $this->parser->parse($icalData);

        // Should find all VEVENT and VTODO components with RRULE
        $eventCount = 0;
        $todoCount = 0;
        $recurringCount = 0;

        foreach ($results as $item) {
            $this->assertArrayHasKey('component', $item);
            $this->assertArrayHasKey('dateTimeContext', $item);

            $componentType = $item['component']->getType();
            if ($componentType === 'VEVENT') {
                ++$eventCount;
            } elseif ($componentType === 'VTODO') {
                ++$todoCount;
            }

            if (isset($item['rrule'])) {
                ++$recurringCount;
            }
        }

        $this->assertGreaterThan(5, $eventCount, 'Should find multiple VEVENT components');
        $this->assertGreaterThan(3, $todoCount, 'Should find multiple VTODO components');
        $this->assertGreaterThan(8, $recurringCount, 'Should find multiple recurring components');
    }

    /**
     * Test component type detection and filtering.
     */
    public function testComponentTypeFiltering(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Multi-Component Test//EN
        
        BEGIN:VEVENT
        UID:event-1@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Daily Event
        END:VEVENT
        
        BEGIN:VTODO
        UID:todo-1@test.example.com
        DTSTART:20250101T120000Z
        DUE:20250101T180000Z
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Weekly Task
        END:VTODO
        
        BEGIN:VJOURNAL
        UID:journal-1@test.example.com
        DTSTART:20250101T150000Z
        SUMMARY:Journal Entry (Should be ignored)
        END:VJOURNAL
        
        BEGIN:VFREEBUSY
        UID:freebusy-1@test.example.com
        DTSTART:20250101T000000Z
        DTEND:20250102T000000Z
        SUMMARY:Free/Busy Info (Should be ignored)
        END:VFREEBUSY
        
        BEGIN:VTODO
        UID:todo-2@test.example.com
        DTSTART:20250102T090000Z
        DUE:20250102T170000Z
        RRULE:FREQ=MONTHLY;COUNT=6
        SUMMARY:Monthly Task
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should only find VEVENT and VTODO components (3 total)
        $this->assertCount(3, $results);

        $foundTypes = [];
        foreach ($results as $item) {
            $foundTypes[] = $item['component']->getType();
        }

        $this->assertContains('VEVENT', $foundTypes);
        $this->assertContains('VTODO', $foundTypes);
        $this->assertNotContains('VJOURNAL', $foundTypes);
        $this->assertNotContains('VFREEBUSY', $foundTypes);

        // Count each type
        $eventCount = array_count_values($foundTypes)['VEVENT'] ?? 0;
        $todoCount = array_count_values($foundTypes)['VTODO'] ?? 0;

        $this->assertEquals(1, $eventCount);
        $this->assertEquals(2, $todoCount);
    }

    /**
     * Test component ordering preservation.
     */
    public function testComponentOrderingPreservation(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Ordering Test//EN
        
        BEGIN:VEVENT
        UID:first-event@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=1
        SUMMARY:First Event
        SEQUENCE:1
        END:VEVENT
        
        BEGIN:VTODO
        UID:first-todo@test.example.com
        DTSTART:20250101T100000Z
        DUE:20250101T120000Z
        RRULE:FREQ=WEEKLY;COUNT=1
        SUMMARY:First Todo
        SEQUENCE:2
        END:VTODO
        
        BEGIN:VEVENT
        UID:second-event@test.example.com
        DTSTART:20250101T140000Z
        DTEND:20250101T150000Z
        RRULE:FREQ=MONTHLY;COUNT=1
        SUMMARY:Second Event
        SEQUENCE:3
        END:VEVENT
        
        BEGIN:VTODO
        UID:second-todo@test.example.com
        DTSTART:20250101T160000Z
        DUE:20250101T180000Z
        RRULE:FREQ=YEARLY;COUNT=1
        SUMMARY:Second Todo
        SEQUENCE:4
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        $this->assertCount(4, $results);

        // Extract sequence numbers to verify ordering
        $sequences = [];
        foreach ($results as $item) {
            $sequence = $item['component']->getProperty('SEQUENCE');
            if ($sequence !== null) {
                $sequences[] = (int) $sequence->getValue();
            }
        }

        // Should maintain original ordering
        $this->assertEquals([1, 2, 3, 4], $sequences, 'Component ordering should be preserved');
    }

    /**
     * Test memory-efficient processing of large files.
     */
    public function testMemoryEfficientProcessing(): void
    {
        // Create a large calendar with many components
        $icalData = $this->createLargeMultiComponentCalendar(50);

        $memoryBefore = memory_get_usage();
        $results = $this->parser->parse($icalData);
        $memoryAfter = memory_get_usage();

        $memoryUsed = $memoryAfter - $memoryBefore;

        // Should process components without excessive memory usage
        // Allow reasonable memory usage for large calendars (under 10MB for 50 components)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Memory usage should be reasonable for large calendars');
        $this->assertGreaterThan(20, count($results), 'Should find many components in large calendar');
    }

    /**
     * Test nested component handling.
     */
    public function testNestedComponentHandling(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Nested Component Test//EN
        
        BEGIN:VEVENT
        UID:parent-event@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Event with Alarm
        
        BEGIN:VALARM
        TRIGGER:-PT15M
        ACTION:DISPLAY
        DESCRIPTION:Reminder
        END:VALARM
        
        BEGIN:VALARM
        TRIGGER:-PT5M
        ACTION:AUDIO
        ATTACH:FMTTYPE=audio/wav:http://example.com/alarm.wav
        END:VALARM
        
        END:VEVENT
        
        BEGIN:VTODO
        UID:parent-todo@test.example.com
        DTSTART:20250101T120000Z
        DUE:20250101T180000Z
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Todo with Alarm
        
        BEGIN:VALARM
        TRIGGER:-PT30M
        ACTION:EMAIL
        ATTENDEE:mailto:admin@example.com
        SUMMARY:Task Reminder
        END:VALARM
        
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should find 2 main components (VEVENT and VTODO)
        $this->assertCount(2, $results);

        // Verify component types
        $types = [];
        foreach ($results as $item) {
            $types[] = $item['component']->getType();
        }

        $this->assertContains('VEVENT', $types);
        $this->assertContains('VTODO', $types);

        // All components should have RRULE
        foreach ($results as $item) {
            $this->assertArrayHasKey('rrule', $item, 'All test components should have RRULE');
        }
    }

    /**
     * Test error handling with malformed multi-component files.
     */
    public function testMalformedMultiComponentHandling(): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        
        BEGIN:VEVENT
        UID:good-event@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Good Event
        END:VEVENT
        
        BEGIN:VTODO
        UID:malformed-todo@test.example.com
        INVALID-PROPERTY:This should be ignored
        DTSTART:20250101T120000Z
        DUE:20250101T180000Z
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Todo with Invalid Property
        END:VTODO
        
        BEGIN:VEVENT
        UID:missing-dtstart@test.example.com
        DTEND:20250101T150000Z
        RRULE:FREQ=MONTHLY;COUNT=3
        SUMMARY:Event without DTSTART (should be skipped)
        END:VEVENT
        
        BEGIN:VTODO
        UID:another-good-todo@test.example.com
        DTSTART:20250101T160000Z
        DUE:20250101T170000Z
        RRULE:FREQ=YEARLY;COUNT=2
        SUMMARY:Another Good Todo
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        // Should gracefully handle malformed components
        // Expect at least 2-3 valid components (skipping ones without proper DTSTART)
        $this->assertGreaterThanOrEqual(2, count($results));
        $this->assertLessThanOrEqual(3, count($results));

        // All returned components should be valid
        foreach ($results as $item) {
            $this->assertArrayHasKey('component', $item);
            $this->assertArrayHasKey('dateTimeContext', $item);
            $this->assertArrayHasKey('rrule', $item);

            // Should have valid datetime context
            $this->assertNotNull($item['dateTimeContext']->getDateTime());
        }
    }

    /**
     * Test processing performance with large component counts.
     */
    public function testLargeComponentCountPerformance(): void
    {
        $componentCount = 100;
        $icalData = $this->createLargeMultiComponentCalendar($componentCount);

        $startTime = microtime(true);
        $results = $this->parser->parse($icalData);
        $endTime = microtime(true);

        $processingTime = $endTime - $startTime;

        // Should process 100 components in under 1 second
        $this->assertLessThan(1.0, $processingTime, 'Should process large calendars quickly');
        $this->assertGreaterThan(50, count($results), 'Should find many valid components');
    }

    /**
     * Create a large multi-component calendar for testing.
     */
    private function createLargeMultiComponentCalendar(int $componentCount): string
    {
        $components = [];

        for ($i = 1; $i <= $componentCount; ++$i) {
            if ($i % 3 === 0) {
                // Create VTODO component
                $components[] = <<<COMPONENT
                BEGIN:VTODO
                UID:todo-{$i}@test.example.com
                DTSTART:20250101T{$this->formatTime($i)}00Z
                DUE:20250101T{$this->formatTime($i + 1)}00Z
                RRULE:FREQ=WEEKLY;COUNT={$i}
                SUMMARY:Todo Task {$i}
                PRIORITY:{$this->getPriority($i)}
                STATUS:NEEDS-ACTION
                END:VTODO
                COMPONENT;
            } else {
                // Create VEVENT component
                $components[] = <<<COMPONENT
                BEGIN:VEVENT
                UID:event-{$i}@test.example.com
                DTSTART:20250101T{$this->formatTime($i)}00Z
                DTEND:20250101T{$this->formatTime($i + 1)}00Z
                RRULE:FREQ=DAILY;COUNT={$i}
                SUMMARY:Event {$i}
                LOCATION:Room {$i}
                STATUS:CONFIRMED
                END:VEVENT
                COMPONENT;
            }

            // Add some non-recurring components occasionally
            if ($i % 7 === 0) {
                $components[] = <<<COMPONENT
                BEGIN:VEVENT
                UID:non-recurring-{$i}@test.example.com
                DTSTART:20250101T{$this->formatTime($i)}30Z
                DTEND:20250101T{$this->formatTime($i + 1)}30Z
                SUMMARY:Non-recurring Event {$i}
                END:VEVENT
                COMPONENT;
            }
        }

        $componentData = implode("\n\n", $components);

        return <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Test Suite//Large Multi-Component Calendar//EN
        CALSCALE:GREGORIAN
        METHOD:PUBLISH
        
        {$componentData}
        
        END:VCALENDAR
        ICAL;
    }

    /**
     * Format time for component creation.
     */
    private function formatTime(int $hour): string
    {
        return str_pad((string) ($hour % 24), 2, '0', STR_PAD_LEFT).'00';
    }

    /**
     * Get priority value for component creation.
     */
    private function getPriority(int $i): int
    {
        return ($i % 9) + 1; // Priority 1-9
    }
}
