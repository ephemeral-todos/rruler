<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\Documentation;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;

/**
 * Integration test for advanced README workflows.
 *
 * Tests complex patterns, error handling, date range filtering,
 * and iCalendar context parsing as documented in README.
 */
final class ReadmeAdvancedWorkflowTest extends TestCase
{
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    /**
     * Test Advanced BYSETPOS Business Patterns from README.
     */
    public function testExecutiveMeetingBysetposWorkflow(): void
    {
        // First Monday of each quarter pattern from README
        $rrule = $this->rruler->parse('FREQ=MONTHLY;INTERVAL=3;BYDAY=MO;BYSETPOS=1');
        $start = new DateTimeImmutable('2024-01-01 10:00:00');

        $meetings = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 4));

        $this->assertCount(4, $meetings, 'Quarterly pattern should generate 4 executive meetings');

        foreach ($meetings as $index => $meeting) {
            $this->assertEquals(1, (int) $meeting->format('N'), "Meeting {$index} should be on Monday");
            $this->assertEquals('10:00', $meeting->format('H:i'), "Meeting {$index} should be at 10 AM");

            // Validate this is the first Monday of the quarter
            $monthNumber = (int) $meeting->format('n');
            $this->assertContains($monthNumber, [1, 4, 7, 10], "Meeting {$index} should be in quarter month");
        }

        // Validate specific quarter months
        $this->assertEquals('2024-01-01', $meetings[0]->format('Y-m-d'), 'Q1 meeting in January');
        $this->assertEquals('2024-04-01', $meetings[1]->format('Y-m-d'), 'Q2 meeting in April');
        $this->assertEquals('2024-07-01', $meetings[2]->format('Y-m-d'), 'Q3 meeting in July');
        $this->assertEquals('2024-10-07', $meetings[3]->format('Y-m-d'), 'Q4 meeting in October');
    }

    /**
     * Test last working day pattern from README.
     */
    public function testLastWorkingDayReportWorkflow(): void
    {
        // Last working day of each month for reports
        $rrule = $this->rruler->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1');
        $start = new DateTimeImmutable('2024-01-31 17:00:00');

        $reports = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 6));

        $this->assertCount(6, $reports, 'Monthly pattern should generate 6 month-end reports');

        foreach ($reports as $index => $report) {
            $dayOfWeek = (int) $report->format('N');
            $this->assertGreaterThanOrEqual(1, $dayOfWeek, "Report {$index} should be on weekday");
            $this->assertLessThanOrEqual(5, $dayOfWeek, "Report {$index} should be on weekday");
            $this->assertEquals('17:00', $report->format('H:i'), "Report {$index} should be at 5 PM");
        }

        // Validate month-end positioning
        foreach ($reports as $index => $report) {
            $month = $report->format('Y-m');
            $lastDayOfMonth = (clone $report)->modify('last day of this month');
            $lastWeekdayOfMonth = $lastDayOfMonth;

            // Find last weekday if last day is weekend
            while ((int) $lastWeekdayOfMonth->format('N') > 5) {
                $lastWeekdayOfMonth = $lastWeekdayOfMonth->modify('-1 day');
            }

            $this->assertEquals(
                $lastWeekdayOfMonth->format('Y-m-d'),
                $report->format('Y-m-d'),
                "Report {$index} should be on last weekday of month {$month}"
            );
        }
    }

    /**
     * Test Multi-Parameter Complex Patterns from README.
     */
    public function testBoardMeetingComplexWorkflow(): void
    {
        // Board meetings: 2nd Thursday of March, June, September, December
        $rrule = $this->rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=TH;BYSETPOS=2');
        $start = new DateTimeImmutable('2024-03-14 14:00:00');

        $boardMeetings = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 8));

        $this->assertCount(8, $boardMeetings, 'Multi-year pattern should generate 8 board meetings');

        // Test pattern for first 4 meetings (2024)
        $expectedQuarters2024 = [3, 6, 9, 12];
        for ($i = 0; $i < 4; ++$i) {
            $meeting = $boardMeetings[$i];
            $this->assertEquals(4, (int) $meeting->format('N'), "Meeting {$i} should be on Thursday");
            $this->assertEquals($expectedQuarters2024[$i], (int) $meeting->format('n'), "Meeting {$i} should be in correct quarter");
            $this->assertEquals('14:00', $meeting->format('H:i'), "Meeting {$i} should be at 2 PM");
            $this->assertEquals('2024', $meeting->format('Y'), "Meeting {$i} should be in 2024");
        }

        // Test pattern continues into 2025
        for ($i = 4; $i < 8; ++$i) {
            $meeting = $boardMeetings[$i];
            $this->assertEquals(4, (int) $meeting->format('N'), "Meeting {$i} should be on Thursday");
            $this->assertEquals('2025', $meeting->format('Y'), "Meeting {$i} should be in 2025");
        }
    }

    /**
     * Test bi-weekly team sync pattern from README.
     */
    public function testBiweeklyTeamSyncWorkflow(): void
    {
        // First and third Friday of every month (bi-weekly team sync)
        $rrule = $this->rruler->parse('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=1,3');
        $start = new DateTimeImmutable('2024-01-05 15:00:00');

        $syncs = iterator_to_array($this->generator->generateOccurrences($rrule, $start, 12));

        $this->assertCount(12, $syncs, 'Bi-weekly pattern should generate 12 team syncs in 6 months');

        // Validate all are Fridays at 3 PM
        foreach ($syncs as $index => $sync) {
            $this->assertEquals(5, (int) $sync->format('N'), "Sync {$index} should be on Friday");
            $this->assertEquals('15:00', $sync->format('H:i'), "Sync {$index} should be at 3 PM");
        }

        // Validate pairs per month (first and third Friday)
        $months = [];
        foreach ($syncs as $sync) {
            $month = $sync->format('Y-m');
            if (!isset($months[$month])) {
                $months[$month] = [];
            }
            $months[$month][] = $sync;
        }

        $this->assertCount(6, $months, 'Should have syncs in 6 different months');

        foreach ($months as $month => $monthSyncs) {
            $this->assertCount(2, $monthSyncs, "Month {$month} should have 2 syncs");
        }
    }

    /**
     * Test Error Handling & Validation workflow from README.
     */
    public function testErrorHandlingValidationWorkflow(): void
    {
        // Implement the parseRruleWithValidation function from README
        $parseRruleWithValidation = function (string $rruleString): ?Rrule {
            try {
                return $this->rruler->parse($rruleString);
            } catch (ValidationException $e) {
                return null;
            } catch (ParseException $e) {
                return null;
            }
        };

        // Test valid pattern from README
        $validPattern = 'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1';
        $validRrule = $parseRruleWithValidation($validPattern);

        $this->assertInstanceOf(Rrule::class, $validRrule, 'Valid RRULE should parse successfully');

        // Generate occurrences as shown in README
        if ($validRrule !== null) {
            $occurrences = iterator_to_array($this->generator->generateOccurrences(
                $validRrule,
                new DateTimeImmutable('2024-01-01'),
                5
            ));

            $this->assertCount(5, $occurrences, 'Valid RRULE should generate expected occurrences');

            foreach ($occurrences as $index => $occurrence) {
                $dayOfWeek = (int) $occurrence->format('N');
                $this->assertContains($dayOfWeek, [1, 3, 5], "Occurrence {$index} should be Monday, Wednesday, or Friday");
            }
        }

        // Test invalid patterns
        $invalidPatterns = [
            'INVALID_RRULE',
            'FREQ=INVALID;COUNT=5',
            'BYSETPOS=1', // Missing FREQ
            '',
        ];

        foreach ($invalidPatterns as $invalidPattern) {
            $result = $parseRruleWithValidation($invalidPattern);
            $this->assertNull($result, "Invalid pattern '{$invalidPattern}' should return null");
        }
    }

    /**
     * Test Date Range Filtering workflow from README.
     */
    public function testDateRangeFilteringWorkflow(): void
    {
        // Complete date range filtering workflow as documented in README
        $rrule = $this->rruler->parse('FREQ=WEEKLY;BYDAY=MO');
        $start = new DateTimeImmutable('2024-01-01 09:00:00');
        $rangeStart = new DateTimeImmutable('2024-06-01');
        $rangeEnd = new DateTimeImmutable('2024-08-31');

        $summerMondays = iterator_to_array($this->generator->generateOccurrencesInRange(
            $rrule,
            $start,
            $rangeStart,
            $rangeEnd
        ));

        $this->assertNotEmpty($summerMondays, 'Date range filtering should produce summer Monday occurrences');

        // Validate all occurrences are within range and on Mondays
        foreach ($summerMondays as $index => $monday) {
            $this->assertGreaterThanOrEqual(
                $rangeStart->getTimestamp(),
                $monday->getTimestamp(),
                "Occurrence {$index} should be after range start"
            );
            $this->assertLessThanOrEqual(
                $rangeEnd->getTimestamp(),
                $monday->getTimestamp(),
                "Occurrence {$index} should be before range end"
            );
            $this->assertEquals(1, (int) $monday->format('N'), "Occurrence {$index} should be on Monday");
            $this->assertEquals('09:00', $monday->format('H:i'), "Occurrence {$index} should maintain start time");
        }

        // Validate expected summer Monday count (13 weeks = 13 Mondays)
        $this->assertCount(13, $summerMondays, 'Summer period should contain 13 Mondays');
    }

    /**
     * Test iCalendar Context Parsing workflow basics from README.
     */
    public function testIcalendarContextParsingWorkflow(): void
    {
        // Test IcalParser instantiation as shown in README
        $icalParser = new IcalParser();
        $this->assertInstanceOf(IcalParser::class, $icalParser, 'IcalParser should be instantiable as documented');

        // Test basic calendar parsing workflow structure
        $basicIcalData = "BEGIN:VCALENDAR\r\n".
                        "VERSION:2.0\r\n".
                        "PRODID:-//Test//Test//EN\r\n".
                        "BEGIN:VEVENT\r\n".
                        "UID:test-event-1\r\n".
                        "DTSTART:20240101T090000Z\r\n".
                        "SUMMARY:Test Event\r\n".
                        "RRULE:FREQ=DAILY;COUNT=3\r\n".
                        "END:VEVENT\r\n".
                        "END:VCALENDAR\r\n";

        $contexts = $icalParser->parse($basicIcalData);

        $this->assertIsArray($contexts, 'parse should return array of contexts');
        $this->assertNotEmpty($contexts, 'parse should produce contexts from valid iCal data');

        // Test workflow with actual API structure (documentation may be inconsistent)
        foreach ($contexts as $context) {
            $this->assertIsArray($context, 'Context should be array structure');

            // Extract actual data from parsed structure
            $component = $context['component'];
            $dateTimeContext = $context['dateTimeContext'];

            $summaryProperty = $component->getProperty('SUMMARY');
            $this->assertNotNull($summaryProperty, 'Context should have summary');
            $this->assertNotEmpty($summaryProperty->getValue(), 'Summary should not be empty');

            if (isset($context['rrule'])) {
                $this->assertInstanceOf(Rrule::class, $context['rrule'], 'Context should have parsed RRULE');

                // Generate occurrences using extracted context
                $occurrences = iterator_to_array($this->generator->generateOccurrences(
                    $context['rrule'],
                    $dateTimeContext->getDateTime(),
                    10
                ));

                $this->assertNotEmpty($occurrences, 'Context RRULE should generate occurrences');
                $this->assertLessThanOrEqual(10, count($occurrences), 'Should respect occurrence limit');
            }
        }
    }
}
