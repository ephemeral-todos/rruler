<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\Property;
use EphemeralTodos\Rruler\Ical\RruleExtractor;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RruleExtractorTest extends TestCase
{
    #[DataProvider('provideComponentsWithRrules')]
    public function testExtractsRrulesFromComponents(Component $component, array $expectedRrules): void
    {
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertCount(count($expectedRrules), $rrules);

        foreach ($expectedRrules as $index => $expected) {
            $this->assertEquals($expected['rrule'], $rrules[$index]['rrule']);
            $this->assertEquals($expected['component_type'], $rrules[$index]['component_type']);
            $this->assertEquals($expected['dtstart'] ?? null, $rrules[$index]['dtstart'] ?? null);
        }
    }

    #[DataProvider('provideMultipleComponents')]
    public function testExtractsRrulesFromMultipleComponents(array $components, array $expectedRrules): void
    {
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponents($components);

        $this->assertCount(count($expectedRrules), $rrules);

        foreach ($expectedRrules as $index => $expected) {
            $this->assertEquals($expected['rrule'], $rrules[$index]['rrule']);
            $this->assertEquals($expected['component_type'], $rrules[$index]['component_type']);
        }
    }

    #[DataProvider('provideNestedComponents')]
    public function testExtractsRrulesFromNestedComponents(Component $component, array $expectedRrules): void
    {
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertCount(count($expectedRrules), $rrules);

        foreach ($expectedRrules as $index => $expected) {
            $this->assertEquals($expected['rrule'], $rrules[$index]['rrule']);
            $this->assertEquals($expected['component_type'], $rrules[$index]['component_type']);
        }
    }

    public function testExtractsRruleWithContext(): void
    {
        $properties = [
            new Property('SUMMARY', 'Weekly Team Meeting'),
            new Property('DTSTART', '20250107T100000Z'),
            new Property('DTEND', '20250107T110000Z'),
            new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU'),
        ];

        $component = new Component('VEVENT', $properties);
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertCount(1, $rrules);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=TU', $rrules[0]['rrule']);
        $this->assertEquals('VEVENT', $rrules[0]['component_type']);
        $this->assertEquals('20250107T100000Z', $rrules[0]['dtstart']);
        $this->assertEquals('Weekly Team Meeting', $rrules[0]['summary'] ?? null);
    }

    public function testHandlesComponentsWithoutRrules(): void
    {
        $properties = [
            new Property('SUMMARY', 'One-time Event'),
            new Property('DTSTART', '20250107T100000Z'),
        ];

        $component = new Component('VEVENT', $properties);
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertEmpty($rrules);
    }

    public function testHandlesEmptyComponents(): void
    {
        $component = new Component('VEVENT');
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertEmpty($rrules);
    }

    public function testHandlesEmptyComponentArray(): void
    {
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponents([]);

        $this->assertEmpty($rrules);
    }

    public function testExtractsMultipleRrulesFromSingleComponent(): void
    {
        // Some iCalendar files might have multiple RRULE properties
        $properties = [
            new Property('SUMMARY', 'Complex Recurring Event'),
            new Property('DTSTART', '20250107T100000Z'),
            new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU'),
            new Property('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=15'),
        ];

        $component = new Component('VEVENT', $properties);
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertCount(2, $rrules);
        $this->assertEquals('FREQ=WEEKLY;BYDAY=TU', $rrules[0]['rrule']);
        $this->assertEquals('FREQ=MONTHLY;BYMONTHDAY=15', $rrules[1]['rrule']);
    }

    public function testPreservesOriginalRruleString(): void
    {
        $originalRrule = 'FREQ=MONTHLY;BYDAY=1MO,3WE;BYSETPOS=-1';
        $properties = [
            new Property('RRULE', $originalRrule),
        ];

        $component = new Component('VEVENT', $properties);
        $extractor = new RruleExtractor();
        $rrules = $extractor->extractFromComponent($component);

        $this->assertCount(1, $rrules);
        $this->assertEquals($originalRrule, $rrules[0]['rrule']);
    }

    public static function provideComponentsWithRrules(): array
    {
        return [
            'VEVENT with simple RRULE' => [
                new Component('VEVENT', [
                    new Property('SUMMARY', 'Daily Standup'),
                    new Property('DTSTART', '20250107T090000Z'),
                    new Property('RRULE', 'FREQ=DAILY'),
                ]),
                [
                    [
                        'rrule' => 'FREQ=DAILY',
                        'component_type' => 'VEVENT',
                        'dtstart' => '20250107T090000Z',
                    ],
                ],
            ],
            'VTODO with complex RRULE' => [
                new Component('VTODO', [
                    new Property('SUMMARY', 'Monthly Report'),
                    new Property('DUE', '20250131T235959Z'),
                    new Property('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=31'),
                ]),
                [
                    [
                        'rrule' => 'FREQ=MONTHLY;BYMONTHDAY=31',
                        'component_type' => 'VTODO',
                        'dtstart' => null,
                    ],
                ],
            ],
            'VEVENT with weekly pattern' => [
                new Component('VEVENT', [
                    new Property('SUMMARY', 'Team Meeting'),
                    new Property('DTSTART', '20250107T140000Z', ['TZID' => 'America/New_York']),
                    new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU,TH;INTERVAL=2'),
                ]),
                [
                    [
                        'rrule' => 'FREQ=WEEKLY;BYDAY=TU,TH;INTERVAL=2',
                        'component_type' => 'VEVENT',
                        'dtstart' => '20250107T140000Z',
                    ],
                ],
            ],
        ];
    }

    public static function provideMultipleComponents(): array
    {
        return [
            'multiple components with RRULEs' => [
                [
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'Daily Standup'),
                        new Property('RRULE', 'FREQ=DAILY'),
                    ]),
                    new Component('VTODO', [
                        new Property('SUMMARY', 'Weekly Review'),
                        new Property('RRULE', 'FREQ=WEEKLY;BYDAY=FR'),
                    ]),
                ],
                [
                    [
                        'rrule' => 'FREQ=DAILY',
                        'component_type' => 'VEVENT',
                    ],
                    [
                        'rrule' => 'FREQ=WEEKLY;BYDAY=FR',
                        'component_type' => 'VTODO',
                    ],
                ],
            ],
            'mixed components with and without RRULEs' => [
                [
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'One-time Event'),
                        new Property('DTSTART', '20250107T100000Z'),
                    ]),
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'Recurring Event'),
                        new Property('RRULE', 'FREQ=MONTHLY;BYDAY=1MO'),
                    ]),
                ],
                [
                    [
                        'rrule' => 'FREQ=MONTHLY;BYDAY=1MO',
                        'component_type' => 'VEVENT',
                    ],
                ],
            ],
        ];
    }

    public static function provideNestedComponents(): array
    {
        return [
            'VCALENDAR with nested VEVENT' => [
                new Component('VCALENDAR', [
                    new Property('VERSION', '2.0'),
                    new Property('PRODID', 'test'),
                ], [
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'Nested Event'),
                        new Property('RRULE', 'FREQ=WEEKLY;BYDAY=MO'),
                    ]),
                ]),
                [
                    [
                        'rrule' => 'FREQ=WEEKLY;BYDAY=MO',
                        'component_type' => 'VEVENT',
                    ],
                ],
            ],
            'VCALENDAR with multiple nested components' => [
                new Component('VCALENDAR', [], [
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'First Event'),
                        new Property('RRULE', 'FREQ=DAILY'),
                    ]),
                    new Component('VTODO', [
                        new Property('SUMMARY', 'Task'),
                        new Property('RRULE', 'FREQ=WEEKLY;BYDAY=FR'),
                    ]),
                    new Component('VEVENT', [
                        new Property('SUMMARY', 'Second Event'),
                        // No RRULE
                    ]),
                ]),
                [
                    [
                        'rrule' => 'FREQ=DAILY',
                        'component_type' => 'VEVENT',
                    ],
                    [
                        'rrule' => 'FREQ=WEEKLY;BYDAY=FR',
                        'component_type' => 'VTODO',
                    ],
                ],
            ],
        ];
    }
}
