<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration;

use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByDayParsingTest extends TestCase
{
    private Rruler $rruler;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
    }

    #[DataProvider('provideByDayRrules')]
    public function testByDayParsing(string $rruleString, array $expectedByDay): void
    {
        $rrule = $this->rruler->parse($rruleString);

        $this->assertTrue($rrule->hasByDay());
        $this->assertEquals($expectedByDay, $rrule->getByDay());
    }

    #[DataProvider('provideByDayToStringRrules')]
    public function testByDayToString(string $rruleString): void
    {
        $rrule = $this->rruler->parse($rruleString);
        $reconstructed = (string) $rrule;

        // Parse the reconstructed string to verify it's valid
        $rruleReconstructed = $this->rruler->parse($reconstructed);

        $this->assertEquals($rrule->getFrequency(), $rruleReconstructed->getFrequency());
        $this->assertEquals($rrule->getByDay(), $rruleReconstructed->getByDay());
    }

    public static function provideByDayRrules(): array
    {
        return [
            // Single weekdays
            [
                'FREQ=WEEKLY;BYDAY=MO',
                [['position' => null, 'weekday' => 'MO']],
            ],
            [
                'FREQ=WEEKLY;BYDAY=FR',
                [['position' => null, 'weekday' => 'FR']],
            ],

            // Multiple weekdays
            [
                'FREQ=WEEKLY;BYDAY=MO,WE,FR',
                [
                    ['position' => null, 'weekday' => 'MO'],
                    ['position' => null, 'weekday' => 'WE'],
                    ['position' => null, 'weekday' => 'FR'],
                ],
            ],

            // Positional weekdays
            [
                'FREQ=MONTHLY;BYDAY=1MO',
                [['position' => 1, 'weekday' => 'MO']],
            ],
            [
                'FREQ=MONTHLY;BYDAY=-1FR',
                [['position' => -1, 'weekday' => 'FR']],
            ],

            // Mixed positional and non-positional
            [
                'FREQ=MONTHLY;BYDAY=1MO,WE,-1FR',
                [
                    ['position' => 1, 'weekday' => 'MO'],
                    ['position' => null, 'weekday' => 'WE'],
                    ['position' => -1, 'weekday' => 'FR'],
                ],
            ],

            // Combined with other parameters
            [
                'FREQ=WEEKLY;INTERVAL=2;BYDAY=SA,SU;COUNT=10',
                [
                    ['position' => null, 'weekday' => 'SA'],
                    ['position' => null, 'weekday' => 'SU'],
                ],
            ],
        ];
    }

    public static function provideByDayToStringRrules(): array
    {
        return [
            ['FREQ=WEEKLY;BYDAY=MO'],
            ['FREQ=WEEKLY;BYDAY=MO,WE,FR'],
            ['FREQ=MONTHLY;BYDAY=1MO'],
            ['FREQ=MONTHLY;BYDAY=-1FR'],
            ['FREQ=MONTHLY;BYDAY=1MO,WE,-1FR'],
            ['FREQ=WEEKLY;INTERVAL=2;BYDAY=SA,SU;COUNT=10'],
        ];
    }

    public function testRruleWithoutByDay(): void
    {
        $rrule = $this->rruler->parse('FREQ=DAILY;INTERVAL=2');

        $this->assertFalse($rrule->hasByDay());
        $this->assertNull($rrule->getByDay());
    }

    public function testByDayToArray(): void
    {
        $rrule = $this->rruler->parse('FREQ=WEEKLY;BYDAY=MO,FR');
        $array = $rrule->toArray();

        $expectedByDay = [
            ['position' => null, 'weekday' => 'MO'],
            ['position' => null, 'weekday' => 'FR'],
        ];

        $this->assertEquals($expectedByDay, $array['byDay']);
    }
}
