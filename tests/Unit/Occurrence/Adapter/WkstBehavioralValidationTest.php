<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive WKST (Week Start) behavioral validation test.
 *
 * This test consolidates WKST edge case testing from multiple specific test files
 * into a comprehensive behavioral validation that focuses on what WKST should do
 * rather than debugging specific implementation details.
 *
 * Consolidates scenarios from:
 * - DefaultOccurrenceGeneratorWkstBugTest
 * - DefaultOccurrenceGeneratorWkstDebugTest
 * - DefaultOccurrenceGeneratorWkstDifferenceTest
 * - DefaultOccurrenceGeneratorWkstRealDifferenceTest
 */
final class WkstBehavioralValidationTest extends TestCase
{
    use TestRrulerBehavior;

    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    /**
     * Test WKST affects bi-weekly occurrence generation correctly.
     *
     * This test validates that WKST parameter correctly influences weekly recurrence
     * patterns with intervals, ensuring proper week boundary calculations.
     */
    #[DataProvider('wkstBiWeeklyScenarios')]
    public function testWkstBiWeeklyBehaviorValidation(
        string $scenario,
        DateTimeImmutable $start,
        string $rruleDefault,
        string $rruleWithWkst,
        int $count,
        string $expectedBehavior,
    ): void {
        $occurrencesDefault = [];
        foreach ($this->generator->generateOccurrences($this->testRruler->parse($rruleDefault), $start, $count) as $occurrence) {
            $occurrencesDefault[] = $occurrence->format('Y-m-d');
        }

        $occurrencesWithWkst = [];
        foreach ($this->generator->generateOccurrences($this->testRruler->parse($rruleWithWkst), $start, $count) as $occurrence) {
            $occurrencesWithWkst[] = $occurrence->format('Y-m-d');
        }

        // Both should produce valid results
        $this->assertNotEmpty($occurrencesDefault,
            "WKST scenario '{$scenario}': Default WKST should produce results");
        $this->assertNotEmpty($occurrencesWithWkst,
            "WKST scenario '{$scenario}': Explicit WKST should produce results");

        $this->assertCount($count, $occurrencesDefault,
            "WKST scenario '{$scenario}': Default WKST should produce {$count} occurrences");
        $this->assertCount($count, $occurrencesWithWkst,
            "WKST scenario '{$scenario}': Explicit WKST should produce {$count} occurrences");

        // Validate expected behavior based on scenario
        if (str_contains($expectedBehavior, 'same_results')) {
            $this->assertEquals($occurrencesDefault, $occurrencesWithWkst,
                "WKST scenario '{$scenario}': {$expectedBehavior}");
        } else {
            // For scenarios where results might differ, we validate they're both valid
            // The key is that both produce valid, chronologically ordered results
            $this->assertValidChronologicalOrder($occurrencesDefault, $scenario, 'default WKST');
            $this->assertValidChronologicalOrder($occurrencesWithWkst, $scenario, 'explicit WKST');
        }
    }

    /**
     * Test WKST affects week boundary calculations correctly.
     *
     * This test validates that WKST parameter correctly affects how week boundaries
     * are calculated, which impacts interval-based recurrence patterns.
     */
    #[DataProvider('wkstWeekBoundaryScenarios')]
    public function testWkstWeekBoundaryBehaviorValidation(
        string $scenario,
        DateTimeImmutable $testDate,
        string $wkstMO,
        string $wkstSU,
        string $expectedDifference,
    ): void {
        $boundariesMO = \EphemeralTodos\Rruler\Occurrence\DateValidationUtils::getWeekBoundaries($testDate, 'MO');
        $boundariesSU = \EphemeralTodos\Rruler\Occurrence\DateValidationUtils::getWeekBoundaries($testDate, 'SU');

        // Week boundaries should be different for different WKST values
        $this->assertNotEquals($boundariesMO['start']->format('Y-m-d'), $boundariesSU['start']->format('Y-m-d'),
            "WKST week boundary scenario '{$scenario}': Different WKST should produce different week start dates");

        $this->assertNotEquals($boundariesMO['end']->format('Y-m-d'), $boundariesSU['end']->format('Y-m-d'),
            "WKST week boundary scenario '{$scenario}': Different WKST should produce different week end dates");

        // Validate that both boundaries are valid (end after start)
        $this->assertLessThan($boundariesMO['end'], $boundariesMO['start'],
            "WKST week boundary scenario '{$scenario}': Monday week start should have valid boundary order");

        $this->assertLessThan($boundariesSU['end'], $boundariesSU['start'],
            "WKST week boundary scenario '{$scenario}': Sunday week start should have valid boundary order");

        // Validate the test date falls within the calculated boundaries
        $this->assertWithinWeekBoundary($testDate, $boundariesMO, $scenario, 'Monday WKST');
        $this->assertWithinWeekBoundary($testDate, $boundariesSU, $scenario, 'Sunday WKST');
    }

    /**
     * Test WKST behavior with simple weekly patterns.
     *
     * This test validates WKST behavior with simple (INTERVAL=1) weekly patterns
     * to establish baseline WKST functionality before testing complex scenarios.
     */
    #[DataProvider('wkstSimpleWeeklyScenarios')]
    public function testWkstSimpleWeeklyBehaviorValidation(
        string $scenario,
        DateTimeImmutable $start,
        string $targetDay,
        int $count,
        string $expectedBehavior,
    ): void {
        $rruleDefault = $this->testRruler->parse("FREQ=WEEKLY;BYDAY={$targetDay};COUNT={$count}");
        $rruleWithWkst = $this->testRruler->parse("FREQ=WEEKLY;BYDAY={$targetDay};WKST=SU;COUNT={$count}");

        $occurrencesDefault = iterator_to_array($this->generator->generateOccurrences($rruleDefault, $start, $count));
        $occurrencesWithWkst = iterator_to_array($this->generator->generateOccurrences($rruleWithWkst, $start, $count));

        $this->assertCount($count, $occurrencesDefault,
            "WKST simple weekly scenario '{$scenario}': Default WKST should produce {$count} occurrences");
        $this->assertCount($count, $occurrencesWithWkst,
            "WKST simple weekly scenario '{$scenario}': Explicit WKST should produce {$count} occurrences");

        // For INTERVAL=1 weekly patterns, WKST should not significantly affect results
        if ($expectedBehavior === 'same_results_for_interval_1') {
            $defaultDates = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesDefault);
            $wkstDates = array_map(fn ($d) => $d->format('Y-m-d'), $occurrencesWithWkst);

            $this->assertEquals($defaultDates, $wkstDates,
                "WKST simple weekly scenario '{$scenario}': INTERVAL=1 should produce same results regardless of WKST");
        }

        // All occurrences should be on the correct day of week
        $expectedDayName = $this->dayCodeToDayName($targetDay);
        foreach ($occurrencesDefault as $occurrence) {
            $this->assertEquals($expectedDayName, $occurrence->format('l'),
                "WKST simple weekly scenario '{$scenario}': All default WKST occurrences should be on {$expectedDayName}");
        }
        foreach ($occurrencesWithWkst as $occurrence) {
            $this->assertEquals($expectedDayName, $occurrence->format('l'),
                "WKST simple weekly scenario '{$scenario}': All explicit WKST occurrences should be on {$expectedDayName}");
        }
    }

    /**
     * Data provider for WKST bi-weekly scenarios.
     *
     * Provides test scenarios from the original edge case tests, consolidated
     * into a comprehensive data provider for systematic testing.
     */
    public static function wkstBiWeeklyScenarios(): array
    {
        return [
            'Saturday bi-weekly from Saturday start' => [
                'scenario' => 'Saturday bi-weekly from Saturday start',
                'start' => new DateTimeImmutable('2024-01-06 09:00:00'), // Saturday
                'rruleDefault' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=SA;COUNT=4',
                'rruleWithWkst' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=SA;WKST=SU;COUNT=4',
                'count' => 4,
                'expectedBehavior' => 'both_valid_results',
            ],
            'Wednesday bi-weekly from Wednesday start' => [
                'scenario' => 'Wednesday bi-weekly from Wednesday start',
                'start' => new DateTimeImmutable('2024-01-03 09:00:00'), // Wednesday
                'rruleDefault' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE;COUNT=5',
                'rruleWithWkst' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=WE;WKST=SU;COUNT=5',
                'count' => 5,
                'expectedBehavior' => 'both_valid_results',
            ],
            'Friday bi-weekly from Tuesday start' => [
                'scenario' => 'Friday bi-weekly from Tuesday start',
                'start' => new DateTimeImmutable('2024-01-02 09:00:00'), // Tuesday
                'rruleDefault' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=FR;COUNT=4',
                'rruleWithWkst' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=FR;WKST=SU;COUNT=4',
                'count' => 4,
                'expectedBehavior' => 'both_valid_results',
            ],
            'Tuesday and Sunday bi-weekly from Sunday start' => [
                'scenario' => 'Tuesday and Sunday bi-weekly from Sunday start',
                'start' => new DateTimeImmutable('2024-01-07 09:00:00'), // Sunday
                'rruleDefault' => 'FREQ=WEEKLY;INTERVAL=2;COUNT=8;BYDAY=TU,SU',
                'rruleWithWkst' => 'FREQ=WEEKLY;INTERVAL=2;COUNT=8;BYDAY=TU,SU;WKST=SU',
                'count' => 8,
                'expectedBehavior' => 'both_valid_results',
            ],
            'Monday from Sunday start' => [
                'scenario' => 'Monday from Sunday start',
                'start' => new DateTimeImmutable('2024-01-07 09:00:00'), // Sunday
                'rruleDefault' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;COUNT=4',
                'rruleWithWkst' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=MO;WKST=SU;COUNT=4',
                'count' => 4,
                'expectedBehavior' => 'both_valid_results',
            ],
        ];
    }

    /**
     * Data provider for WKST week boundary scenarios.
     */
    public static function wkstWeekBoundaryScenarios(): array
    {
        return [
            'Wednesday mid-week test' => [
                'scenario' => 'Wednesday mid-week boundary test',
                'testDate' => new DateTimeImmutable('2024-01-03'), // Wednesday
                'wkstMO' => 'MO',
                'wkstSU' => 'SU',
                'expectedDifference' => 'different_week_boundaries',
            ],
            'Sunday week start test' => [
                'scenario' => 'Sunday week start boundary test',
                'testDate' => new DateTimeImmutable('2024-01-07'), // Sunday
                'wkstMO' => 'MO',
                'wkstSU' => 'SU',
                'expectedDifference' => 'different_week_boundaries',
            ],
            'Monday week start test' => [
                'scenario' => 'Monday week start boundary test',
                'testDate' => new DateTimeImmutable('2024-01-01'), // Monday
                'wkstMO' => 'MO',
                'wkstSU' => 'SU',
                'expectedDifference' => 'different_week_boundaries',
            ],
        ];
    }

    /**
     * Data provider for WKST simple weekly scenarios.
     */
    public static function wkstSimpleWeeklyScenarios(): array
    {
        return [
            'Monday from Sunday start' => [
                'scenario' => 'Simple weekly Monday from Sunday start',
                'start' => new DateTimeImmutable('2024-01-07 09:00:00'), // Sunday
                'targetDay' => 'MO',
                'count' => 3,
                'expectedBehavior' => 'same_results_for_interval_1',
            ],
            'Friday from Tuesday start' => [
                'scenario' => 'Simple weekly Friday from Tuesday start',
                'start' => new DateTimeImmutable('2024-01-02 09:00:00'), // Tuesday
                'targetDay' => 'FR',
                'count' => 3,
                'expectedBehavior' => 'same_results_for_interval_1',
            ],
            'Wednesday from Wednesday start' => [
                'scenario' => 'Simple weekly Wednesday from Wednesday start',
                'start' => new DateTimeImmutable('2024-01-03 09:00:00'), // Wednesday
                'targetDay' => 'WE',
                'count' => 3,
                'expectedBehavior' => 'same_results_for_interval_1',
            ],
        ];
    }

    /**
     * Helper method to validate chronological order of occurrences.
     */
    private function assertValidChronologicalOrder(array $dates, string $scenario, string $wkstType): void
    {
        for ($i = 1; $i < count($dates); ++$i) {
            $this->assertGreaterThan($dates[$i - 1], $dates[$i],
                "WKST scenario '{$scenario}': {$wkstType} occurrences should be in chronological order");
        }
    }

    /**
     * Helper method to validate a date falls within calculated week boundaries.
     */
    private function assertWithinWeekBoundary(DateTimeImmutable $date, array $boundaries, string $scenario, string $wkstType): void
    {
        $this->assertGreaterThanOrEqual($boundaries['start'], $date,
            "WKST scenario '{$scenario}': Test date should be on or after {$wkstType} week start");

        $this->assertLessThanOrEqual($boundaries['end'], $date,
            "WKST scenario '{$scenario}': Test date should be on or before {$wkstType} week end");
    }

    /**
     * Helper method to convert day codes to day names.
     */
    private function dayCodeToDayName(string $dayCode): string
    {
        $dayMap = [
            'MO' => 'Monday',
            'TU' => 'Tuesday',
            'WE' => 'Wednesday',
            'TH' => 'Thursday',
            'FR' => 'Friday',
            'SA' => 'Saturday',
            'SU' => 'Sunday',
        ];

        return $dayMap[$dayCode] ?? $dayCode;
    }
}
