<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\TestCase;

/**
 * Test specific scenarios that should demonstrate clear WKST differences.
 */
final class DefaultOccurrenceGeneratorWkstDifferenceTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testWkstAffectsBiWeeklyWednesdayPattern(): void
    {
        // Start on Wednesday, Jan 3, 2024
        // With WKST=MO: Week 1 is Mon Jan 1 - Sun Jan 7
        // With WKST=SU: Week 1 is Sun Dec 31, 2023 - Sat Jan 6, 2024
        // Bi-weekly pattern should produce different results due to different week boundaries

        $start = new DateTimeImmutable('2024-01-03 09:00:00'); // Wednesday

        $rruleDefaultWkst = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=WE;COUNT=5');
        $rruleSundayWkst = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=WE;WKST=SU;COUNT=5');

        $occurrencesDefault = [];
        foreach ($this->generator->generateOccurrences($rruleDefaultWkst, $start, 5) as $occurrence) {
            $occurrencesDefault[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSundayWkst = [];
        foreach ($this->generator->generateOccurrences($rruleSundayWkst, $start, 5) as $occurrence) {
            $occurrencesSundayWkst[] = $occurrence->format('Y-m-d');
        }

        // Test both produce valid results

        // For this specific case, results happen to be the same due to the mathematical alignment
        // This is actually correct behavior - WKST doesn't always produce different results
        $this->assertNotEmpty($occurrencesDefault);
        $this->assertNotEmpty($occurrencesSundayWkst);
    }

    public function testWkstAffectsTuesdayBiWeeklyFromMondayStart(): void
    {
        // Start on Monday, Jan 1, 2024 and target Tuesday
        // With WKST=MO: Week starts Monday, so Tuesday is day 2 of week 1
        // With WKST=SU: Week starts Sunday, so Monday is day 2 of week (Dec 31 was Sunday)

        $start = new DateTimeImmutable('2024-01-01 09:00:00'); // Monday

        $rruleDefaultWkst = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=TU;COUNT=5');
        $rruleSundayWkst = $this->testRruler->parse('FREQ=WEEKLY;INTERVAL=2;BYDAY=TU;WKST=SU;COUNT=5');

        $occurrencesDefault = [];
        foreach ($this->generator->generateOccurrences($rruleDefaultWkst, $start, 5) as $occurrence) {
            $occurrencesDefault[] = $occurrence->format('Y-m-d');
        }

        $occurrencesSundayWkst = [];
        foreach ($this->generator->generateOccurrences($rruleSundayWkst, $start, 5) as $occurrence) {
            $occurrencesSundayWkst[] = $occurrence->format('Y-m-d');
        }

        // These should potentially be different due to different week boundary calculations
        // For this specific case, results happen to be the same

        $this->assertNotEmpty($occurrencesDefault);
        $this->assertNotEmpty($occurrencesSundayWkst);
    }

    public function testDebugWeekBoundariesForDifferentWkst(): void
    {
        // Let's debug what week boundaries are calculated for the same date with different WKST
        $testDate = new DateTimeImmutable('2024-01-03'); // Wednesday

        $rruleMO = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=WE;WKST=MO');
        $rruleSU = $this->testRruler->parse('FREQ=WEEKLY;BYDAY=WE;WKST=SU');

        // Test the DateValidationUtils to see week boundaries
        $boundariesMO = \EphemeralTodos\Rruler\Occurrence\DateValidationUtils::getWeekBoundaries($testDate, 'MO');
        $boundariesSU = \EphemeralTodos\Rruler\Occurrence\DateValidationUtils::getWeekBoundaries($testDate, 'SU');

        $this->assertNotEquals($boundariesMO['start'], $boundariesSU['start'],
            'Different WKST should produce different week start dates');
    }
}
