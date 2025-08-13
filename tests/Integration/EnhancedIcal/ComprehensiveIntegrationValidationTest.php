<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\EnhancedIcal;

use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Testing\Utilities\EnhancedIcalCompatibilityFramework;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive integration tests using all collected test data.
 *
 * This test suite validates the complete enhanced iCalendar compatibility
 * implementation against all test data files and compatibility scenarios.
 */
final class ComprehensiveIntegrationValidationTest extends TestCase
{
    private IcalParser $parser;
    private EnhancedIcalCompatibilityFramework $compatibilityFramework;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
        $this->compatibilityFramework = new EnhancedIcalCompatibilityFramework();
    }

    /**
     * Test comprehensive parsing of synthetic test data.
     */
    public function testSyntheticDataComprehensiveParsing(): void
    {
        $testDataDir = __DIR__.'/../../data/enhanced-ical/synthetic/';
        $testFile = $testDataDir.'complex-mixed-components.ics';

        if (!file_exists($testFile)) {
            $this->markTestSkipped('Synthetic test data file not found: '.$testFile);
        }

        $icalContent = file_get_contents($testFile);
        $this->assertNotFalse($icalContent, 'Should read synthetic test data file');

        $results = $this->parser->parse($icalContent);

        // Comprehensive validation of parsing results
        $this->assertGreaterThan(10, count($results), 'Should find multiple components in synthetic data');

        $eventCount = 0;
        $todoCount = 0;
        $recurringCount = 0;
        $timezoneAwareCount = 0;

        foreach ($results as $item) {
            // Basic structure validation
            $this->assertArrayHasKey('component', $item);
            $this->assertArrayHasKey('dateTimeContext', $item);
            $this->assertArrayHasKey('rrule', $item);

            // Component type analysis
            $componentType = $item['component']->getType();
            if ($componentType === 'VEVENT') {
                ++$eventCount;
            } elseif ($componentType === 'VTODO') {
                ++$todoCount;
            }

            // RRULE validation
            if (isset($item['rrule'])) {
                ++$recurringCount;
                $this->assertNotEmpty((string) $item['rrule']);
            }

            // Timezone awareness validation
            $dateTimeContext = $item['dateTimeContext'];
            if ($dateTimeContext->hasTimezone()) {
                ++$timezoneAwareCount;
            }

            // DateTime validation
            $this->assertNotNull($dateTimeContext->getDateTime());
            $this->assertInstanceOf(\DateTimeImmutable::class, $dateTimeContext->getDateTime());
        }

        // Statistical validation
        $this->assertGreaterThan(5, $eventCount, 'Should find multiple VEVENT components');
        $this->assertGreaterThan(3, $todoCount, 'Should find multiple VTODO components');
        $this->assertEquals(count($results), $recurringCount, 'All test components should have RRULE');
        $this->assertGreaterThan(0, $timezoneAwareCount, 'Should find timezone-aware components');
    }

    /**
     * Test extended date format support integration.
     */
    public function testExtendedDateFormatIntegration(): void
    {
        // Test various date formats found in real-world scenarios
        $dateFormats = [
            // Standard RFC 5545 formats
            '20250101T090000Z' => '2025-01-01 09:00:00',
            '20250101T090000' => '2025-01-01 09:00:00',
            '20250101' => '2025-01-01 00:00:00',

            // Timezone-aware formats
            'DTSTART;TZID=America/New_York:20250101T090000' => 'America/New_York',
            'DUE;TZID=Europe/London:20250101T180000' => 'Europe/London',
        ];

        foreach ($dateFormats as $format => $expected) {
            $formatStr = (string) $format;
            if (str_contains($formatStr, 'DTSTART') || str_contains($formatStr, 'DUE')) {
                // Test as property line
                $this->validatePropertyFormat($formatStr, $expected);
            } else {
                // Test as datetime value
                $this->validateDateTimeFormat($formatStr, $expected);
            }
        }
    }

    /**
     * Test multi-component processing integration.
     */
    public function testMultiComponentProcessingIntegration(): void
    {
        $largeIcalData = $this->generateLargeTestCalendar(25);

        $startTime = microtime(true);
        $results = $this->parser->parse($largeIcalData);
        $endTime = microtime(true);

        $processingTime = $endTime - $startTime;

        // Performance validation
        $this->assertLessThan(0.5, $processingTime, 'Should process large calendars quickly');

        // Content validation
        $this->assertGreaterThan(15, count($results), 'Should find multiple components');

        // Component type distribution validation
        $types = [];
        foreach ($results as $item) {
            $types[] = $item['component']->getType();
        }

        $this->assertContains('VEVENT', $types, 'Should include VEVENT components');
        $this->assertContains('VTODO', $types, 'Should include VTODO components');

        // Order preservation validation
        $this->validateComponentOrdering($results);
    }

    /**
     * Test property extraction edge cases integration.
     */
    public function testPropertyExtractionEdgeCasesIntegration(): void
    {
        $edgeCaseData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Integration Test Suite//Enhanced iCal Testing//EN
        
        BEGIN:VEVENT
        UID:edge-case-1@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Event with Standard Properties
        END:VEVENT
        
        BEGIN:VTODO
        UID:edge-case-2@test.example.com
        DTSTART:20250102T140000Z
        DUE:20250102T180000Z
        RRULE:FREQ=WEEKLY;COUNT=4
        SUMMARY:Todo with Both DTSTART and DUE
        END:VTODO
        
        BEGIN:VTODO
        UID:edge-case-3@test.example.com
        DTSTART:20250103T100000Z
        RRULE:FREQ=MONTHLY;COUNT=3
        SUMMARY:Todo with DTSTART only (no DUE)
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($edgeCaseData);

        $this->assertCount(3, $results, 'Should handle all edge case components');

        // Validate each edge case
        $uids = [];
        foreach ($results as $item) {
            $uid = $item['component']->getProperty('UID')->getValue();
            $uids[] = $uid;

            // All should have valid datetime context
            $this->assertNotNull($item['dateTimeContext']->getDateTime());

            // All should have RRULE
            $this->assertArrayHasKey('rrule', $item);
        }

        $this->assertContains('edge-case-1@test.example.com', $uids);
        $this->assertContains('edge-case-2@test.example.com', $uids);
        $this->assertContains('edge-case-3@test.example.com', $uids);
    }

    /**
     * Test compatibility framework integration.
     */
    public function testCompatibilityFrameworkIntegration(): void
    {
        $testData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Compatibility Test Suite//Enhanced iCal Testing//EN
        
        BEGIN:VEVENT
        UID:compat-test@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=5
        SUMMARY:Compatibility Test Event
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        // Test parsing comparison
        $comparisonResult = $this->compatibilityFramework->compareParsingResults($testData);

        $this->assertIsArray($comparisonResult);
        $this->assertArrayHasKey('rruler_results', $comparisonResult);
        $this->assertArrayHasKey('sabre_results', $comparisonResult);
        $this->assertIsArray($comparisonResult['rruler_results']);
        $this->assertIsArray($comparisonResult['sabre_results']);

        // Test occurrence generation comparison
        $occurrenceResult = $this->compatibilityFramework->compareOccurrenceGeneration($testData, 10);

        $this->assertIsArray($occurrenceResult);
        $this->assertArrayHasKey('rruler_occurrences', $occurrenceResult);
        $this->assertArrayHasKey('sabre_occurrences', $occurrenceResult);
        $this->assertIsArray($occurrenceResult['rruler_occurrences']);
    }

    /**
     * Test performance benchmarking integration.
     */
    public function testPerformanceBenchmarkingIntegration(): void
    {
        $largeTestData = $this->generateLargeTestCalendar(50);

        $benchmarkResult = $this->compatibilityFramework->benchmarkPerformance($largeTestData, 3);

        $this->assertIsArray($benchmarkResult);
        $this->assertArrayHasKey('rruler_avg_time', $benchmarkResult);
        $this->assertArrayHasKey('sabre_avg_time', $benchmarkResult);
        $this->assertArrayHasKey('rruler_memory_usage', $benchmarkResult);
        $this->assertArrayHasKey('sabre_memory_usage', $benchmarkResult);

        // Performance validation
        $this->assertLessThan(1.0, $benchmarkResult['rruler_avg_time'], 'Rruler should be reasonably fast');
        $this->assertLessThan(50 * 1024 * 1024, $benchmarkResult['rruler_memory_usage'], 'Memory usage should be reasonable');
    }

    /**
     * Test complete workflow integration.
     */
    public function testCompleteWorkflowIntegration(): void
    {
        // Test complete workflow from parsing to occurrence generation
        $workflowData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Workflow Test Suite//Enhanced iCal Testing//EN
        
        BEGIN:VEVENT
        UID:workflow-daily@test.example.com
        DTSTART:20250101T090000Z
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;INTERVAL=2;COUNT=5
        SUMMARY:Every Other Day Event
        END:VEVENT
        
        BEGIN:VTODO
        UID:workflow-weekly@test.example.com
        DTSTART:20250101T140000Z
        DUE:20250101T180000Z
        RRULE:FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10
        SUMMARY:Weekday Task
        END:VTODO
        
        END:VCALENDAR
        ICAL;

        // Step 1: Parse iCalendar data
        $results = $this->parser->parse($workflowData);
        $this->assertCount(2, $results, 'Should parse both components');

        // Step 2: Validate parsing results
        foreach ($results as $item) {
            $this->assertArrayHasKey('component', $item);
            $this->assertArrayHasKey('dateTimeContext', $item);
            $this->assertArrayHasKey('rrule', $item);

            $rrule = $item['rrule'];
            $startDateTime = $item['dateTimeContext']->getDateTime();

            // Step 3: Validate RRULE properties
            $this->assertNotNull($rrule->getFrequency());
            $this->assertNotNull($rrule->getCount());

            // Step 4: Generate test occurrences (basic validation)
            $occurrenceCount = $rrule->getCount();
            $this->assertGreaterThan(0, $occurrenceCount, 'Should have occurrence count');
            $this->assertLessThanOrEqual(10, $occurrenceCount, 'Should have reasonable occurrence count');
        }
    }

    /**
     * Validate property format parsing.
     */
    private function validatePropertyFormat(string $propertyLine, string $expectedTimezone): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Property Format Test//EN
        
        BEGIN:VEVENT
        UID:property-test@test.example.com
        {$propertyLine}
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=1
        SUMMARY:Property Format Test
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        if (!empty($results)) {
            $item = $results[0];
            $this->assertEquals($expectedTimezone, $item['dateTimeContext']->getTimezone());
        }
    }

    /**
     * Validate datetime format parsing.
     */
    private function validateDateTimeFormat(string $datetime, string $expectedFormat): void
    {
        $icalData = <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//DateTime Format Test//EN
        
        BEGIN:VEVENT
        UID:datetime-test@test.example.com
        DTSTART:{$datetime}
        DTEND:20250101T100000Z
        RRULE:FREQ=DAILY;COUNT=1
        SUMMARY:DateTime Format Test
        END:VEVENT
        
        END:VCALENDAR
        ICAL;

        $results = $this->parser->parse($icalData);

        if (!empty($results)) {
            $item = $results[0];
            $this->assertEquals($expectedFormat, $item['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
        }
    }

    /**
     * Validate component ordering is preserved.
     */
    private function validateComponentOrdering(array $results): void
    {
        $sequences = [];
        foreach ($results as $item) {
            $sequence = $item['component']->getProperty('SEQUENCE');
            if ($sequence !== null) {
                $sequences[] = (int) $sequence->getValue();
            }
        }

        if (!empty($sequences)) {
            // Check if sequences are in order (allowing for gaps)
            $sortedSequences = $sequences;
            sort($sortedSequences);
            $this->assertEquals($sortedSequences, $sequences, 'Component ordering should be preserved');
        }
    }

    /**
     * Generate large test calendar for performance testing.
     */
    private function generateLargeTestCalendar(int $componentCount): string
    {
        $components = [];

        for ($i = 1; $i <= $componentCount; ++$i) {
            $hour = str_pad((string) ($i % 24), 2, '0', STR_PAD_LEFT);
            $sequence = $i;

            if ($i % 3 === 0) {
                $components[] = <<<COMPONENT
                BEGIN:VTODO
                UID:large-test-todo-{$i}@test.example.com
                DTSTART:20250101T{$hour}0000Z
                DUE:20250101T{$hour}3000Z
                RRULE:FREQ=WEEKLY;COUNT={$i}
                SUMMARY:Large Test Todo {$i}
                SEQUENCE:{$sequence}
                END:VTODO
                COMPONENT;
            } else {
                $components[] = <<<COMPONENT
                BEGIN:VEVENT
                UID:large-test-event-{$i}@test.example.com
                DTSTART:20250101T{$hour}0000Z
                DTEND:20250101T{$hour}3000Z
                RRULE:FREQ=DAILY;COUNT={$i}
                SUMMARY:Large Test Event {$i}
                SEQUENCE:{$sequence}
                END:VEVENT
                COMPONENT;
            }
        }

        $componentData = implode("\n\n", $components);

        return <<<ICAL
        BEGIN:VCALENDAR
        VERSION:2.0
        PRODID:-//Large Test Suite//Enhanced iCal Testing//EN
        
        {$componentData}
        
        END:VCALENDAR
        ICAL;
    }
}
