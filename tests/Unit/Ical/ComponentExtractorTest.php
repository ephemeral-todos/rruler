<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\ComponentExtractor;
use EphemeralTodos\Rruler\Ical\Property;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComponentExtractorTest extends TestCase
{
    #[DataProvider('provideBasicComponents')]
    public function testExtractsBasicComponents(array $properties, array $expectedComponents): void
    {
        $extractor = new ComponentExtractor();
        $components = $extractor->extract($properties);

        $this->assertCount(count($expectedComponents), $components);

        foreach ($expectedComponents as $index => $expected) {
            $this->assertInstanceOf(Component::class, $components[$index]);
            $this->assertEquals($expected['type'], $components[$index]->getType());
            $this->assertCount(count($expected['properties']), $components[$index]->getProperties());
        }
    }

    #[DataProvider('provideNestedComponents')]
    public function testExtractsNestedComponents(array $properties, array $expectedComponents): void
    {
        $extractor = new ComponentExtractor();
        $components = $extractor->extract($properties);

        $this->assertCount(count($expectedComponents), $components);

        // Verify the structure matches expectations
        foreach ($expectedComponents as $index => $expected) {
            $component = $components[$index];
            $this->assertEquals($expected['type'], $component->getType());

            if (isset($expected['children'])) {
                $children = $component->getChildren();
                $this->assertCount(count($expected['children']), $children);

                foreach ($expected['children'] as $childIndex => $expectedChild) {
                    $this->assertEquals($expectedChild['type'], $children[$childIndex]->getType());
                }
            }
        }
    }

    #[DataProvider('provideRealWorldComponents')]
    public function testExtractsRealWorldComponents(array $properties, array $expectedComponents): void
    {
        $extractor = new ComponentExtractor();
        $components = $extractor->extract($properties);

        $this->assertCount(count($expectedComponents), $components);

        foreach ($expectedComponents as $index => $expected) {
            $component = $components[$index];
            $this->assertEquals($expected['type'], $component->getType());

            // Check for specific properties we care about
            if (isset($expected['hasRrule'])) {
                $rruleProperty = $component->getProperty('RRULE');
                if ($expected['hasRrule']) {
                    $this->assertNotNull($rruleProperty);
                } else {
                    $this->assertNull($rruleProperty);
                }
            }

            if (isset($expected['hasSummary'])) {
                $summaryProperty = $component->getProperty('SUMMARY');
                if ($expected['hasSummary']) {
                    $this->assertNotNull($summaryProperty);
                } else {
                    $this->assertNull($summaryProperty);
                }
            }
        }
    }

    public function testHandlesEmptyPropertyArray(): void
    {
        $extractor = new ComponentExtractor();
        $components = $extractor->extract([]);

        $this->assertEmpty($components);
    }

    public function testHandlesPropertiesWithoutComponents(): void
    {
        $properties = [
            new Property('SUMMARY', 'Standalone Summary'),
            new Property('DTSTART', '20250107T100000Z'),
        ];

        $extractor = new ComponentExtractor();
        $components = $extractor->extract($properties);

        $this->assertEmpty($components);
    }

    public function testHandlesUnmatchedBeginEnd(): void
    {
        $properties = [
            new Property('BEGIN', 'VEVENT'),
            new Property('SUMMARY', 'Event without END'),
        ];

        $extractor = new ComponentExtractor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unmatched BEGIN component');

        $extractor->extract($properties);
    }

    public function testHandlesUnmatchedEnd(): void
    {
        $properties = [
            new Property('SUMMARY', 'Event before END'),
            new Property('END', 'VEVENT'),
        ];

        $extractor = new ComponentExtractor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unmatched END component');

        $extractor->extract($properties);
    }

    public function testHandlesMismatchedBeginEnd(): void
    {
        $properties = [
            new Property('BEGIN', 'VEVENT'),
            new Property('SUMMARY', 'Mismatched Event'),
            new Property('END', 'VTODO'),
        ];

        $extractor = new ComponentExtractor();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mismatched component types');

        $extractor->extract($properties);
    }

    public static function provideBasicComponents(): array
    {
        return [
            'single VEVENT' => [
                [
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'Team Meeting'),
                    new Property('DTSTART', '20250107T100000Z'),
                    new Property('END', 'VEVENT'),
                ],
                [
                    [
                        'type' => 'VEVENT',
                        'properties' => ['SUMMARY', 'DTSTART'],
                    ],
                ],
            ],
            'single VTODO' => [
                [
                    new Property('BEGIN', 'VTODO'),
                    new Property('SUMMARY', 'Complete task'),
                    new Property('DUE', '20250107T235959Z'),
                    new Property('END', 'VTODO'),
                ],
                [
                    [
                        'type' => 'VTODO',
                        'properties' => ['SUMMARY', 'DUE'],
                    ],
                ],
            ],
            'multiple components' => [
                [
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'First Event'),
                    new Property('END', 'VEVENT'),
                    new Property('BEGIN', 'VTODO'),
                    new Property('SUMMARY', 'First Task'),
                    new Property('END', 'VTODO'),
                ],
                [
                    [
                        'type' => 'VEVENT',
                        'properties' => ['SUMMARY'],
                    ],
                    [
                        'type' => 'VTODO',
                        'properties' => ['SUMMARY'],
                    ],
                ],
            ],
        ];
    }

    public static function provideNestedComponents(): array
    {
        return [
            'VCALENDAR with VEVENT' => [
                [
                    new Property('BEGIN', 'VCALENDAR'),
                    new Property('VERSION', '2.0'),
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'Nested Event'),
                    new Property('END', 'VEVENT'),
                    new Property('END', 'VCALENDAR'),
                ],
                [
                    [
                        'type' => 'VCALENDAR',
                        'children' => [
                            ['type' => 'VEVENT'],
                        ],
                    ],
                ],
            ],
            'VCALENDAR with multiple components' => [
                [
                    new Property('BEGIN', 'VCALENDAR'),
                    new Property('VERSION', '2.0'),
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'First Event'),
                    new Property('END', 'VEVENT'),
                    new Property('BEGIN', 'VTODO'),
                    new Property('SUMMARY', 'First Task'),
                    new Property('END', 'VTODO'),
                    new Property('END', 'VCALENDAR'),
                ],
                [
                    [
                        'type' => 'VCALENDAR',
                        'children' => [
                            ['type' => 'VEVENT'],
                            ['type' => 'VTODO'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public static function provideRealWorldComponents(): array
    {
        return [
            'VEVENT with RRULE' => [
                [
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'Weekly Meeting'),
                    new Property('DTSTART', '20250107T100000Z'),
                    new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU'),
                    new Property('END', 'VEVENT'),
                ],
                [
                    [
                        'type' => 'VEVENT',
                        'hasRrule' => true,
                        'hasSummary' => true,
                    ],
                ],
            ],
            'VTODO without RRULE' => [
                [
                    new Property('BEGIN', 'VTODO'),
                    new Property('SUMMARY', 'One-time task'),
                    new Property('DUE', '20250107T235959Z'),
                    new Property('END', 'VTODO'),
                ],
                [
                    [
                        'type' => 'VTODO',
                        'hasRrule' => false,
                        'hasSummary' => true,
                    ],
                ],
            ],
            'VEVENT with complex properties' => [
                [
                    new Property('BEGIN', 'VEVENT'),
                    new Property('SUMMARY', 'Complex Event'),
                    new Property('DTSTART', '20250107T100000Z', ['TZID' => 'America/New_York']),
                    new Property('DTEND', '20250107T120000Z', ['TZID' => 'America/New_York']),
                    new Property('ATTENDEE', 'mailto:john@example.com', ['CN' => 'John Doe']),
                    new Property('RRULE', 'FREQ=MONTHLY;BYDAY=1MO'),
                    new Property('END', 'VEVENT'),
                ],
                [
                    [
                        'type' => 'VEVENT',
                        'hasRrule' => true,
                        'hasSummary' => true,
                    ],
                ],
            ],
        ];
    }
}
