<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Parser\Ast\ByDayNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ByDayNodeTest extends TestCase
{
    #[DataProvider('provideHappyPathData')]
    public function testHappyPath(array $expected, string $input): void
    {
        $node = new ByDayNode($input);

        $this->assertEquals($expected, $node->getValue());
        $this->assertEquals($input, $node->getRawValue());
        $this->assertEquals('BYDAY', $node->getName());
    }

    #[DataProvider('provideUnhappyPathData')]
    public function testUnhappyPath(string $expectedMessage, string $input): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage($expectedMessage);

        new ByDayNode($input);
    }

    public static function provideHappyPathData(): array
    {
        return [
            // Single weekdays
            [
                [['position' => null, 'weekday' => 'MO']],
                'MO',
            ],
            [
                [['position' => null, 'weekday' => 'TU']],
                'TU',
            ],
            [
                [['position' => null, 'weekday' => 'WE']],
                'WE',
            ],
            [
                [['position' => null, 'weekday' => 'TH']],
                'TH',
            ],
            [
                [['position' => null, 'weekday' => 'FR']],
                'FR',
            ],
            [
                [['position' => null, 'weekday' => 'SA']],
                'SA',
            ],
            [
                [['position' => null, 'weekday' => 'SU']],
                'SU',
            ],

            // Multiple weekdays
            [
                [
                    ['position' => null, 'weekday' => 'MO'],
                    ['position' => null, 'weekday' => 'WE'],
                    ['position' => null, 'weekday' => 'FR'],
                ],
                'MO,WE,FR',
            ],
            [
                [
                    ['position' => null, 'weekday' => 'SA'],
                    ['position' => null, 'weekday' => 'SU'],
                ],
                'SA,SU',
            ],

            // Positional weekdays
            [
                [['position' => 1, 'weekday' => 'MO']],
                '1MO',
            ],
            [
                [['position' => -1, 'weekday' => 'FR']],
                '-1FR',
            ],
            [
                [['position' => 2, 'weekday' => 'TU']],
                '2TU',
            ],
            [
                [['position' => -2, 'weekday' => 'TH']],
                '-2TH',
            ],

            // Mixed positional and non-positional
            [
                [
                    ['position' => 1, 'weekday' => 'MO'],
                    ['position' => null, 'weekday' => 'WE'],
                    ['position' => -1, 'weekday' => 'FR'],
                ],
                '1MO,WE,-1FR',
            ],

            // Edge cases for positions
            [
                [['position' => 53, 'weekday' => 'MO']],
                '53MO',
            ],
            [
                [['position' => -53, 'weekday' => 'SU']],
                '-53SU',
            ],

            // With positive sign
            [
                [['position' => 1, 'weekday' => 'MO']],
                '+1MO',
            ],
            [
                [['position' => 2, 'weekday' => 'TU']],
                '+2TU',
            ],

            // Whitespace handling
            [
                [
                    ['position' => null, 'weekday' => 'MO'],
                    ['position' => null, 'weekday' => 'WE'],
                ],
                'MO, WE',
            ],
            [
                [
                    ['position' => 1, 'weekday' => 'MO'],
                    ['position' => -1, 'weekday' => 'FR'],
                ],
                ' 1MO , -1FR ',
            ],
        ];
    }

    public static function provideUnhappyPathData(): array
    {
        return [
            // Empty values
            ['BYDAY cannot be empty', ''],

            // Invalid weekdays
            [
                "Invalid BYDAY specification 'XX'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'XX',
            ],
            [
                "Invalid BYDAY specification 'AB'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'AB',
            ],
            [
                "Invalid BYDAY specification 'MON'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'MON',
            ],

            // Case sensitivity
            [
                "Invalid BYDAY specification 'mo'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'mo',
            ],
            [
                "Invalid BYDAY specification 'Mo'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'Mo',
            ],

            // Invalid positions
            [
                "Invalid position '0' in BYDAY. Position must be between -53 and 53, excluding 0",
                '0MO',
            ],
            [
                "Invalid position '54' in BYDAY. Position must be between -53 and 53, excluding 0",
                '54MO',
            ],
            [
                "Invalid position '-54' in BYDAY. Position must be between -53 and 53, excluding 0",
                '-54MO',
            ],

            // Invalid formats
            [
                "Invalid BYDAY specification 'M'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'M',
            ],
            [
                "Invalid BYDAY specification 'MOO'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'MOO',
            ],
            [
                "Invalid BYDAY specification '1'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                '1',
            ],
            [
                "Invalid BYDAY specification 'MO1'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'MO1',
            ],

            // Empty items in list
            ['BYDAY cannot contain empty day specifications', 'MO,,FR'],
            ['BYDAY cannot contain empty day specifications', ',MO'],
            ['BYDAY cannot contain empty day specifications', 'MO,'],

            // Invalid characters
            [
                "Invalid BYDAY specification '1.5MO'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                '1.5MO',
            ],
            [
                "Invalid BYDAY specification 'MO-1'. Expected format: [position]WEEKDAY (e.g., 'MO', '1MO', '-1FR')",
                'MO-1',
            ],
        ];
    }

    public function testGetName(): void
    {
        $node = new ByDayNode('MO');
        $this->assertEquals('BYDAY', $node->getName());
    }

    public function testComplexMixedValues(): void
    {
        $node = new ByDayNode('1MO,WE,-1FR,SA,2SU');
        $expected = [
            ['position' => 1, 'weekday' => 'MO'],
            ['position' => null, 'weekday' => 'WE'],
            ['position' => -1, 'weekday' => 'FR'],
            ['position' => null, 'weekday' => 'SA'],
            ['position' => 2, 'weekday' => 'SU'],
        ];

        $this->assertEquals($expected, $node->getValue());
    }
}
