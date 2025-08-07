<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\ComponentType;
use EphemeralTodos\Rruler\Ical\DateTimeContext;
use EphemeralTodos\Rruler\Ical\DateTimeContextExtractor;
use EphemeralTodos\Rruler\Ical\Property;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeContextExtractorTest extends TestCase
{
    #[DataProvider('provideVEventComponents')]
    public function testExtractsDtStartFromVEventComponents(Component $component, ?DateTimeContext $expectedContext): void
    {
        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        if ($expectedContext === null) {
            $this->assertNull($context);
        } else {
            $this->assertInstanceOf(DateTimeContext::class, $context);
            $this->assertEquals($expectedContext->getComponentType(), $context->getComponentType());
            $this->assertEquals($expectedContext->getDateTime()->format('Y-m-d H:i:s'), $context->getDateTime()->format('Y-m-d H:i:s'));
            $this->assertEquals($expectedContext->getTimezone(), $context->getTimezone());
        }
    }

    #[DataProvider('provideVTodoComponents')]
    public function testExtractsDueFromVTodoComponents(Component $component, ?DateTimeContext $expectedContext): void
    {
        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        if ($expectedContext === null) {
            $this->assertNull($context);
        } else {
            $this->assertInstanceOf(DateTimeContext::class, $context);
            $this->assertEquals($expectedContext->getComponentType(), $context->getComponentType());
            $this->assertEquals($expectedContext->getDateTime()->format('Y-m-d H:i:s'), $context->getDateTime()->format('Y-m-d H:i:s'));
            $this->assertEquals($expectedContext->getTimezone(), $context->getTimezone());
        }
    }

    #[DataProvider('provideVTodoWithFallback')]
    public function testVTodoFallsBackToDtStart(Component $component, DateTimeContext $expectedContext): void
    {
        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertInstanceOf(DateTimeContext::class, $context);
        $this->assertEquals(ComponentType::VTODO, $context->getComponentType());
        $this->assertEquals($expectedContext->getDateTime()->format('Y-m-d H:i:s'), $context->getDateTime()->format('Y-m-d H:i:s'));
    }

    public function testHandlesComponentWithoutDateTimeProperties(): void
    {
        $component = new Component('VEVENT', [
            new Property('SUMMARY', 'Event without date'),
            new Property('DESCRIPTION', 'No DTSTART property'),
        ]);

        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertNull($context);
    }

    public function testHandlesUnsupportedComponentType(): void
    {
        $component = new Component('VJOURNAL', [
            new Property('DTSTART', '20250107T100000Z'),
        ]);

        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertNull($context);
    }

    #[DataProvider('provideTimezoneProperties')]
    public function testExtractsTimezoneFromTzidParameter(Component $component, string $expectedTimezone): void
    {
        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertInstanceOf(DateTimeContext::class, $context);
        $this->assertEquals($expectedTimezone, $context->getTimezone());
    }

    #[DataProvider('provideOriginalValues')]
    public function testPreservesOriginalPropertyValue(Component $component, string $expectedOriginalValue): void
    {
        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertInstanceOf(DateTimeContext::class, $context);
        $this->assertEquals($expectedOriginalValue, $context->getOriginalValue());
    }

    #[DataProvider('provideDateFormats')]
    public function testHandlesDifferentDateFormats(string $propertyValue, array $parameters, string $expectedFormattedDateTime): void
    {
        $component = new Component('VEVENT', [
            new Property('DTSTART', $propertyValue, $parameters),
        ]);

        $extractor = new DateTimeContextExtractor();
        $context = $extractor->extractFromComponent($component);

        $this->assertInstanceOf(DateTimeContext::class, $context);
        $this->assertEquals($expectedFormattedDateTime, $context->getDateTime()->format('Y-m-d H:i:s'));
    }

    public function testExtractsMultipleComponentsFromNestedStructure(): void
    {
        $veventComponent = new Component('VEVENT', [
            new Property('DTSTART', '20250107T100000Z'),
            new Property('SUMMARY', 'Nested Event'),
        ]);

        $vtodoComponent = new Component('VTODO', [
            new Property('DUE', '20250108T120000Z'),
            new Property('SUMMARY', 'Nested Task'),
        ]);

        $vcalendarComponent = new Component('VCALENDAR', [
            new Property('VERSION', '2.0'),
        ], [$veventComponent, $vtodoComponent]);

        $extractor = new DateTimeContextExtractor();
        $contexts = $extractor->extractFromComponents([$vcalendarComponent]);

        $this->assertCount(2, $contexts);

        // First should be VEVENT
        $this->assertEquals(ComponentType::VEVENT, $contexts[0]->getComponentType());
        $this->assertEquals('2025-01-07 10:00:00', $contexts[0]->getDateTime()->format('Y-m-d H:i:s'));

        // Second should be VTODO
        $this->assertEquals(ComponentType::VTODO, $contexts[1]->getComponentType());
        $this->assertEquals('2025-01-08 12:00:00', $contexts[1]->getDateTime()->format('Y-m-d H:i:s'));
    }

    public static function provideVEventComponents(): array
    {
        return [
            'VEVENT with DTSTART UTC' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T100000Z'),
                    new Property('SUMMARY', 'UTC Event'),
                ]),
                new DateTimeContext(
                    new \DateTimeImmutable('2025-01-07 10:00:00 UTC'),
                    null,
                    ComponentType::VEVENT,
                    '20250107T100000Z'
                ),
            ],
            'VEVENT with DTSTART local' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T100000', ['TZID' => 'America/New_York']),
                    new Property('SUMMARY', 'Local Event'),
                ]),
                new DateTimeContext(
                    new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York')),
                    'America/New_York',
                    ComponentType::VEVENT,
                    '20250107T100000'
                ),
            ],
            'VEVENT without DTSTART' => [
                new Component('VEVENT', [
                    new Property('SUMMARY', 'Event without DTSTART'),
                ]),
                null,
            ],
        ];
    }

    public static function provideVTodoComponents(): array
    {
        return [
            'VTODO with DUE UTC' => [
                new Component('VTODO', [
                    new Property('DUE', '20250107T235959Z'),
                    new Property('SUMMARY', 'UTC Task'),
                ]),
                new DateTimeContext(
                    new \DateTimeImmutable('2025-01-07 23:59:59 UTC'),
                    null,
                    ComponentType::VTODO,
                    '20250107T235959Z'
                ),
            ],
            'VTODO with DUE local' => [
                new Component('VTODO', [
                    new Property('DUE', '20250107T180000', ['TZID' => 'Europe/London']),
                    new Property('SUMMARY', 'London Task'),
                ]),
                new DateTimeContext(
                    new \DateTimeImmutable('2025-01-07 18:00:00', new \DateTimeZone('Europe/London')),
                    'Europe/London',
                    ComponentType::VTODO,
                    '20250107T180000'
                ),
            ],
            'VTODO without DUE' => [
                new Component('VTODO', [
                    new Property('SUMMARY', 'Task without DUE'),
                ]),
                null,
            ],
        ];
    }

    public static function provideVTodoWithFallback(): array
    {
        return [
            'VTODO with DTSTART fallback' => [
                new Component('VTODO', [
                    new Property('DTSTART', '20250107T090000Z'),
                    new Property('SUMMARY', 'Task with DTSTART'),
                ]),
                new DateTimeContext(
                    new \DateTimeImmutable('2025-01-07 09:00:00 UTC'),
                    null,
                    ComponentType::VTODO
                ),
            ],
        ];
    }

    public static function provideTimezoneProperties(): array
    {
        return [
            'VEVENT with Eastern timezone' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T100000', ['TZID' => 'America/New_York']),
                ]),
                'America/New_York',
            ],
            'VEVENT with Pacific timezone' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T100000', ['TZID' => 'America/Los_Angeles']),
                ]),
                'America/Los_Angeles',
            ],
        ];
    }

    public static function provideOriginalValues(): array
    {
        return [
            'UTC timestamp' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T103045Z'),
                ]),
                '20250107T103045Z',
            ],
            'Local timestamp' => [
                new Component('VTODO', [
                    new Property('DUE', '20250107T180000', ['TZID' => 'Europe/London']),
                ]),
                '20250107T180000',
            ],
        ];
    }

    public static function provideDateFormats(): array
    {
        return [
            'UTC timestamp' => [
                '20250107T100000Z',
                [],
                '2025-01-07 10:00:00',
            ],
            'local timestamp with timezone' => [
                '20250107T150000',
                ['TZID' => 'America/New_York'],
                '2025-01-07 15:00:00',
            ],
            'date only' => [
                '20250107',
                [],
                '2025-01-07 00:00:00',
            ],
            'floating time' => [
                '20250107T120000',
                [],
                '2025-01-07 12:00:00',
            ],
        ];
    }
}
