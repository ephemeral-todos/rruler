<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\Documentation;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;

/**
 * Integration test validating README code examples work as documented.
 *
 * This ensures documentation examples stay current with the API and provides
 * end-to-end validation of complete user workflows from the README.
 */
final class ReadmeCodeExampleTest extends TestCase
{
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    /**
     * Test the complete Quick Start workflow from README.
     *
     * Validates the entire user workflow from parsing to occurrence generation,
     * ensuring the documented output matches actual results.
     */
    public function testQuickStartWorkflow(): void
    {
        // Complete Quick Start workflow as documented in README
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=DAILY;COUNT=5');

        $generator = new DefaultOccurrenceGenerator();
        $startDate = new DateTimeImmutable('2024-01-01 09:00:00');
        $occurrences = $generator->generateOccurrences($rrule, $startDate);

        $results = iterator_to_array($occurrences);

        // Verify workflow produces expected output as documented
        $this->assertCount(5, $results, 'Quick Start should generate exactly 5 daily occurrences');

        // Validate documented output sequence
        $expectedDates = [
            new DateTimeImmutable('2024-01-01 09:00:00'),
            new DateTimeImmutable('2024-01-02 09:00:00'),
            new DateTimeImmutable('2024-01-03 09:00:00'),
            new DateTimeImmutable('2024-01-04 09:00:00'),
            new DateTimeImmutable('2024-01-05 09:00:00'),
        ];

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(
                $expectedDate,
                $results[$index],
                "Occurrence {$index} should match documented output"
            );
        }

        // Validate workflow maintains time consistency (behavioral test)
        $originalTime = $startDate->format('H:i:s');
        foreach ($results as $result) {
            $this->assertEquals($originalTime, $result->format('H:i:s'), 'All occurrences should maintain original start time');
        }
    }

    /**
     * Test the complete Weekly Recurring Patterns workflow from README.
     *
     * Validates weekly team meeting pattern generation and time consistency
     * across multiple weeks as documented.
     */
    public function testWeeklyTeamMeetingWorkflow(): void
    {
        // Complete Weekly Recurring Patterns workflow as documented in README
        $rrule = $this->rruler->parse('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');
        $start = new DateTimeImmutable('2024-01-02 14:00:00');

        // Get next 8 weeks of meetings as documented
        $meetings = [];
        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start, 8) as $meeting) {
            $meetings[] = $meeting;
            ++$count;
            if ($count >= 8) {
                break;
            }
        }

        $this->assertCount(8, $meetings, 'Weekly pattern should generate 8 team meetings');

        // Validate complete workflow: all meetings maintain original day/time pattern
        $originalDayOfWeek = (int) $start->format('N'); // Tuesday = 2
        $originalTime = $start->format('H:i');

        foreach ($meetings as $index => $meeting) {
            $this->assertEquals($originalDayOfWeek, (int) $meeting->format('N'), "Meeting {$index} should be on same weekday as original");
            $this->assertEquals($originalTime, $meeting->format('H:i'), "Meeting {$index} should maintain original time");
        }

        // Validate weekly progression
        $expectedDates = [
            '2024-01-02', '2024-01-09', '2024-01-16', '2024-01-23',
            '2024-01-30', '2024-02-06', '2024-02-13', '2024-02-20',
        ];

        foreach ($expectedDates as $index => $expectedDate) {
            $this->assertEquals(
                $expectedDate,
                $meetings[$index]->format('Y-m-d'),
                "Meeting {$index} should be on {$expectedDate}"
            );
        }
    }

    /**
     * Test the complete Complex Yearly Patterns workflow from README.
     *
     * Validates quarterly business review pattern using advanced BYSETPOS
     * with multiple BY* parameters as documented.
     */
    public function testQuarterlyBusinessReviewWorkflow(): void
    {
        // Complete Complex Yearly Patterns workflow as documented in README
        $rrule = $this->rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;BYSETPOS=-1');
        $start = new DateTimeImmutable('2024-03-29 10:00:00');

        $reviews = [];
        $count = 0;
        foreach ($this->generator->generateOccurrences($rrule, $start, 4) as $review) {
            $reviews[] = $review;
            ++$count;
            if ($count >= 4) {
                break;
            }
        }

        $this->assertCount(4, $reviews, 'Quarterly pattern should generate 4 business reviews');

        // Validate complete workflow: last Friday of each quarter
        $expectedQuarters = [
            ['month' => 3, 'name' => 'March'],
            ['month' => 6, 'name' => 'June'],
            ['month' => 9, 'name' => 'September'],
            ['month' => 12, 'name' => 'December'],
        ];

        $originalDayOfWeek = (int) $start->format('N'); // Friday = 5
        $originalTime = $start->format('H:i');

        foreach ($reviews as $index => $review) {
            $quarter = $expectedQuarters[$index];

            $this->assertEquals($originalDayOfWeek, (int) $review->format('N'), "Review {$index} should be on same weekday as original");
            $this->assertEquals($quarter['month'], (int) $review->format('n'), "Review {$index} should be in {$quarter['name']}");
            $this->assertEquals($originalTime, $review->format('H:i'), "Review {$index} should maintain original time");

            // Validate this is indeed the last Friday of the month
            $lastDayOfMonth = (clone $review)->modify('last day of this month');
            $lastFridayOfMonth = $lastDayOfMonth->modify('last friday');

            $this->assertEquals(
                $lastFridayOfMonth->format('Y-m-d'),
                $review->format('Y-m-d'),
                "Review {$index} should be on the last Friday of {$quarter['name']}"
            );
        }
    }

    /**
     * Test additional complex patterns from README to ensure comprehensive workflow validation.
     */
    public function testDailyStandupWorkdayWorkflow(): void
    {
        // Daily standup meetings pattern from README - weekdays only
        $rrule = $this->rruler->parse('FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=10');
        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $meetings = iterator_to_array($this->generator->generateOccurrences($rrule, $start));

        $this->assertCount(10, $meetings, 'Daily weekday pattern should generate 10 standup meetings');

        // Validate no weekends are included and time consistency
        $originalTime = $start->format('H:i');

        foreach ($meetings as $index => $meeting) {
            $dayOfWeek = (int) $meeting->format('N');
            $this->assertGreaterThanOrEqual(1, $dayOfWeek, "Meeting {$index} should be on weekday");
            $this->assertLessThanOrEqual(5, $dayOfWeek, "Meeting {$index} should be on weekday");
            $this->assertEquals($originalTime, $meeting->format('H:i'), "Meeting {$index} should maintain original time");
        }

        // Validate progression skips weekends (behavioral test using DateTime objects)
        $expectedDates = [
            new DateTimeImmutable('2024-01-01 09:00:00'), // Monday
            new DateTimeImmutable('2024-01-02 09:00:00'), // Tuesday
            new DateTimeImmutable('2024-01-05 09:00:00'), // Friday (index 4)
            new DateTimeImmutable('2024-01-08 09:00:00'), // Next Monday (index 5)
        ];

        $this->assertEquals($expectedDates[0], $meetings[0], 'First meeting on Monday');
        $this->assertEquals($expectedDates[1], $meetings[1], 'Second meeting on Tuesday');
        $this->assertEquals($expectedDates[2], $meetings[4], 'Fifth meeting on Friday');
        $this->assertEquals($expectedDates[3], $meetings[5], 'Sixth meeting on next Monday');
    }

    /**
     * Test monthly pattern workflow from README.
     */
    public function testMonthlyReportWorkflow(): void
    {
        // Monthly reports pattern from README with UNTIL termination
        $rrule = $this->rruler->parse('FREQ=MONTHLY;BYMONTHDAY=15;UNTIL=20241231T235959Z');
        $start = new DateTimeImmutable('2024-01-15 09:00:00');

        $reports = iterator_to_array($this->generator->generateOccurrences($rrule, $start));

        $this->assertCount(12, $reports, 'Monthly pattern should generate 12 monthly reports for 2024');

        // Validate each report maintains original day and time pattern
        $originalDay = (int) $start->format('d'); // 15th
        $originalTime = $start->format('H:i');

        foreach ($reports as $index => $report) {
            $this->assertEquals($originalDay, (int) $report->format('d'), "Report {$index} should be on same day as original");
            $this->assertEquals($originalTime, $report->format('H:i'), "Report {$index} should maintain original time");
        }

        // Validate progression through months (behavioral test with DateTime objects)
        $firstExpected = new DateTimeImmutable('2024-01-15 09:00:00');
        $lastExpected = new DateTimeImmutable('2024-12-15 09:00:00');

        $this->assertEquals($firstExpected, $reports[0], 'First report in January');
        $this->assertEquals($lastExpected, $reports[11], 'Last report in December');
    }
}
