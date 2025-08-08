<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Documentation;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;

final class ReadmeCodeExampleTest extends TestCase
{
    public function testQuickStartExample(): void
    {
        // Code from README Quick Start section
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=DAILY;COUNT=5');

        $generator = new DefaultOccurrenceGenerator();
        $startDate = new DateTimeImmutable('2024-01-01 09:00:00');
        $occurrences = $generator->generateOccurrences($rrule, $startDate);

        $results = iterator_to_array($occurrences);
        
        // Verify we get exactly 5 occurrences
        $this->assertCount(5, $results);
        
        // Verify the first and last occurrences match the expected output
        $this->assertEquals(
            new DateTimeImmutable('2024-01-01 09:00:00'),
            $results[0]
        );
        $this->assertEquals(
            new DateTimeImmutable('2024-01-05 09:00:00'),
            $results[4]
        );
    }

    public function testWeeklyPatternExample(): void
    {
        // Code from README Weekly Recurring Patterns section
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');
        $start = new DateTimeImmutable('2024-01-02 14:00:00');

        $generator = new DefaultOccurrenceGenerator();
        
        // Get next 8 weeks of meetings
        $meetings = [];
        $count = 0;
        foreach ($generator->generateOccurrences($rrule, $start, 8) as $meeting) {
            $meetings[] = $meeting;
            $count++;
            if ($count >= 8) break;
        }

        $this->assertCount(8, $meetings);
        
        // All meetings should be on Tuesday
        foreach ($meetings as $meeting) {
            $this->assertEquals(2, (int) $meeting->format('N'), 'Meeting should be on Tuesday');
            $this->assertEquals('14:00', $meeting->format('H:i'), 'Meeting should be at 2 PM');
        }
    }

    public function testComplexYearlyPatternExample(): void
    {
        // Code from README Complex Yearly Patterns section
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;BYSETPOS=-1');
        $start = new DateTimeImmutable('2024-03-29 10:00:00');

        $generator = new DefaultOccurrenceGenerator();
        
        $reviews = [];
        $count = 0;
        foreach ($generator->generateOccurrences($rrule, $start, 4) as $review) {
            $reviews[] = $review;
            $count++;
            if ($count >= 4) break;
        }

        $this->assertCount(4, $reviews);
        
        // Should be the last Friday of March, June, September, December for year 2024
        // Note: The pattern starts from the start date year and continues
        $expectedMonthsInSequence = [6, 9, 12, 3]; // June 2024, Sep 2024, Dec 2024, March 2025
        foreach ($reviews as $index => $review) {
            $this->assertEquals(5, (int) $review->format('N'), 'Review should be on Friday');
            $this->assertEquals($expectedMonthsInSequence[$index], (int) $review->format('n'), 'Review should be in expected month sequence');
        }
    }
}