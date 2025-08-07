<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

/**
 * Generates systematic RRULE patterns for compatibility testing.
 *
 * This class creates comprehensive test cases covering all combinations
 * of RRULE parameters supported by both Rruler and sabre/vobject.
 */
final class RrulePatternGenerator
{
    /**
     * Generate basic frequency patterns with various intervals.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateBasicFrequencyPatterns(): array
    {
        $patterns = [];
        $frequencies = ['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'];
        $intervals = [1, 2, 3, 5, 10];

        foreach ($frequencies as $freq) {
            foreach ($intervals as $interval) {
                $patterns[] = [
                    'rrule' => "FREQ={$freq};INTERVAL={$interval};COUNT=10",
                    'start' => '2025-01-01',
                    'description' => "{$freq} every {$interval} with count 10",
                ];
            }
        }

        return $patterns;
    }

    /**
     * Generate patterns with COUNT termination.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateCountPatterns(): array
    {
        $patterns = [];
        $counts = [1, 3, 5, 10, 25, 100];

        foreach ($counts as $count) {
            $patterns[] = [
                'rrule' => "FREQ=DAILY;COUNT={$count}",
                'start' => '2025-01-01',
                'description' => "Daily with count {$count}",
            ];
        }

        return $patterns;
    }

    /**
     * Generate patterns with UNTIL termination.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateUntilPatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=DAILY;UNTIL=20250131T235959Z',
                'start' => '2025-01-01',
                'description' => 'Daily until end of January',
            ],
            [
                'rrule' => 'FREQ=WEEKLY;UNTIL=20250301T235959Z',
                'start' => '2025-01-01',
                'description' => 'Weekly until March 1st',
            ],
            [
                'rrule' => 'FREQ=MONTHLY;UNTIL=20251231T235959Z',
                'start' => '2025-01-01',
                'description' => 'Monthly until end of year',
            ],
            [
                'rrule' => 'FREQ=YEARLY;UNTIL=20301231T235959Z',
                'start' => '2025-01-01',
                'description' => 'Yearly for 5 years',
            ],
        ];
    }

    /**
     * Generate BYDAY patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateByDayPatterns(): array
    {
        $patterns = [];
        $weekdays = ['MO', 'TU', 'WE', 'TH', 'FR', 'SA', 'SU'];
        $combinations = [
            ['MO'],
            ['MO', 'WE', 'FR'],
            ['TU', 'TH'],
            ['SA', 'SU'],
            ['MO', 'TU', 'WE', 'TH', 'FR'], // Weekdays
            ['SA', 'SU'], // Weekend
        ];

        // Simple BYDAY patterns
        foreach ($combinations as $days) {
            $dayString = implode(',', $days);
            $patterns[] = [
                'rrule' => "FREQ=WEEKLY;BYDAY={$dayString};COUNT=10",
                'start' => '2025-01-01',
                'description' => 'Weekly on '.implode(', ', $days),
            ];
        }

        // Positional BYDAY patterns for monthly
        $positions = [1, 2, 3, -1, -2];
        foreach ($positions as $position) {
            foreach (['MO', 'FR', 'SU'] as $day) {
                $patterns[] = [
                    'rrule' => "FREQ=MONTHLY;BYDAY={$position}{$day};COUNT=6",
                    'start' => '2025-01-01',
                    'description' => "Monthly {$position} {$day}",
                ];
            }
        }

        return $patterns;
    }

    /**
     * Generate BYMONTHDAY patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateByMonthDayPatterns(): array
    {
        $patterns = [];
        $monthdays = [1, 5, 15, 28, 31, -1, -5, -15];
        $combinations = [
            [1],
            [15],
            [-1],
            [1, 15],
            [1, -1],
            [5, 10, 15, 20, 25],
        ];

        foreach ($combinations as $days) {
            $dayString = implode(',', $days);
            $patterns[] = [
                'rrule' => "FREQ=MONTHLY;BYMONTHDAY={$dayString};COUNT=12",
                'start' => '2025-01-01',
                'description' => 'Monthly on days '.implode(', ', $days),
            ];
        }

        return $patterns;
    }

    /**
     * Generate BYMONTH patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateByMonthPatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=1;COUNT=5',
                'start' => '2025-01-01',
                'description' => 'Yearly in January',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=3,6,9,12;COUNT=8',
                'start' => '2025-01-01',
                'description' => 'Yearly quarterly',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=1,7;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'Yearly bi-annual',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=6,7,8;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'Yearly summer months',
            ],
        ];
    }

    /**
     * Generate BYWEEKNO patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateByWeekNoPatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=1;COUNT=5',
                'start' => '2025-01-01',
                'description' => 'Yearly first week',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=13,26,39,52;COUNT=8',
                'start' => '2025-01-01',
                'description' => 'Yearly quarterly weeks',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=53;COUNT=3',
                'start' => '2025-01-01',
                'description' => 'Yearly week 53 (leap week)',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=1,52;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'Yearly first and last weeks',
            ],
        ];
    }

    /**
     * Generate BYSETPOS patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateBySetPosPatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'First Monday of each month',
            ],
            [
                'rrule' => 'FREQ=MONTHLY;BYDAY=FR;BYSETPOS=-1;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'Last Friday of each month',
            ],
            [
                'rrule' => 'FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1;COUNT=12',
                'start' => '2025-01-01',
                'description' => 'First and last weekday of each month',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=3;BYDAY=SU;BYSETPOS=-1;COUNT=3',
                'start' => '2025-01-01',
                'description' => 'Last Sunday of March (DST transition)',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=13,26;BYSETPOS=1;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'First day of weeks 13 and 26',
            ],
        ];
    }

    /**
     * Generate complex multi-parameter patterns.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateComplexPatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=MONTHLY;INTERVAL=2;BYDAY=MO;BYSETPOS=1;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'First Monday every 2 months',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=TH;BYSETPOS=-1;COUNT=8',
                'start' => '2025-01-01',
                'description' => 'Last Thursday of each quarter',
            ],
            [
                'rrule' => 'FREQ=WEEKLY;INTERVAL=2;BYDAY=TU,TH;COUNT=10',
                'start' => '2025-01-01',
                'description' => 'Tuesday and Thursday every 2 weeks',
            ],
            [
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=1,15;BYSETPOS=2;COUNT=6',
                'start' => '2025-01-01',
                'description' => 'Second occurrence of 1st or 15th each month',
            ],
        ];
    }

    /**
     * Generate all pattern categories for comprehensive testing.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateAllPatterns(): array
    {
        return array_merge(
            self::generateBasicFrequencyPatterns(),
            self::generateCountPatterns(),
            self::generateUntilPatterns(),
            self::generateByDayPatterns(),
            self::generateByMonthDayPatterns(),
            self::generateByMonthPatterns(),
            self::generateByWeekNoPatterns(),
            self::generateBySetPosPatterns(),
            self::generateComplexPatterns()
        );
    }

    /**
     * Generate edge case patterns for boundary testing.
     *
     * @return array<array{rrule: string, start: string, description: string}>
     */
    public static function generateEdgeCasePatterns(): array
    {
        return [
            [
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=29;COUNT=12',
                'start' => '2025-01-01',
                'description' => '29th of each month (February edge case)',
            ],
            [
                'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=31;COUNT=12',
                'start' => '2025-01-01',
                'description' => '31st of each month (short month edge case)',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYMONTHDAY=29;BYMONTH=2;COUNT=5',
                'start' => '2024-01-01',
                'description' => 'February 29th (leap year edge case)',
            ],
            [
                'rrule' => 'FREQ=DAILY;COUNT=1',
                'start' => '2025-01-01',
                'description' => 'Single occurrence',
            ],
            [
                'rrule' => 'FREQ=YEARLY;BYWEEKNO=53;COUNT=5',
                'start' => '2020-01-01',
                'description' => 'Week 53 across multiple years',
            ],
        ];
    }
}
