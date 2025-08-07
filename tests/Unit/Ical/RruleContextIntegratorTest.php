<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\Component;
use EphemeralTodos\Rruler\Ical\ComponentType;
use EphemeralTodos\Rruler\Ical\DateTimeContext;
use EphemeralTodos\Rruler\Ical\Property;
use EphemeralTodos\Rruler\Ical\RruleContextIntegrator;
use EphemeralTodos\Rruler\Rrule;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RruleContextIntegratorTest extends TestCase
{
    /**
     * Helper method to compare RRULE strings ignoring parameter order.
     */
    private function assertRruleStringsEquivalent(string $expected, string $actual): void
    {
        // Parse both strings into parameter arrays
        $expectedParams = $this->parseRruleString($expected);
        $actualParams = $this->parseRruleString($actual);

        $this->assertEquals($expectedParams, $actualParams,
            "RRULE parameters should match regardless of order. Expected: {$expected}, Actual: {$actual}");
    }

    /**
     * Parse an RRULE string into an associative array of parameters.
     */
    private function parseRruleString(string $rrule): array
    {
        $params = [];
        $parts = explode(';', $rrule);

        foreach ($parts as $part) {
            if (strpos($part, '=') !== false) {
                [$key, $value] = explode('=', $part, 2);
                $params[$key] = $value;
            }
        }

        return $params;
    }

    #[DataProvider('provideVEventWithRrule')]
    public function testExtractsRruleWithDateTimeContextFromVEvent(
        Component $component,
        string $expectedRruleString,
        string $expectedDateTimeFormat,
        ?string $expectedTimezone,
    ): void {
        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertArrayHasKey('rrule', $result);
        $this->assertArrayHasKey('dateTimeContext', $result);
        $this->assertArrayHasKey('component', $result);

        // Check RRULE (allow for parameter reordering by the parser)
        $this->assertInstanceOf(Rrule::class, $result['rrule']);
        $actualRruleString = (string) $result['rrule'];

        // Parse both strings to compare their components, ignoring order
        $this->assertRruleStringsEquivalent($expectedRruleString, $actualRruleString);

        // Check DateTimeContext
        $this->assertInstanceOf(DateTimeContext::class, $result['dateTimeContext']);
        $this->assertEquals($expectedDateTimeFormat, $result['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedTimezone, $result['dateTimeContext']->getTimezone());
        $this->assertEquals(ComponentType::VEVENT, $result['dateTimeContext']->getComponentType());

        // Check Component
        $this->assertSame($component, $result['component']);
    }

    #[DataProvider('provideVTodoWithRrule')]
    public function testExtractsRruleWithDateTimeContextFromVTodo(
        Component $component,
        string $expectedRruleString,
        string $expectedDateTimeFormat,
        ?string $expectedTimezone,
    ): void {
        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);

        $this->assertCount(1, $results);

        $result = $results[0];
        $this->assertEquals(ComponentType::VTODO, $result['dateTimeContext']->getComponentType());
        $this->assertRruleStringsEquivalent($expectedRruleString, (string) $result['rrule']);
        $this->assertEquals($expectedDateTimeFormat, $result['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals($expectedTimezone, $result['dateTimeContext']->getTimezone());
    }

    public function testHandlesComponentWithRruleButNoDateTime(): void
    {
        $component = new Component('VEVENT', [
            new Property('RRULE', 'FREQ=DAILY'),
            new Property('SUMMARY', 'Event without DTSTART'),
        ]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);

        $this->assertEmpty($results);
    }

    public function testHandlesComponentWithDateTimeButNoRrule(): void
    {
        $component = new Component('VEVENT', [
            new Property('DTSTART', '20250107T100000Z'),
            new Property('SUMMARY', 'Event without RRULE'),
        ]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);

        $this->assertEmpty($results);
    }

    public function testHandlesMultipleRrulesInSingleComponent(): void
    {
        $component = new Component('VEVENT', [
            new Property('DTSTART', '20250107T100000Z'),
            new Property('RRULE', 'FREQ=DAILY'),
            new Property('RRULE', 'FREQ=WEEKLY;BYDAY=MO'),
            new Property('SUMMARY', 'Event with multiple RRULEs'),
        ]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);

        $this->assertCount(2, $results);

        $this->assertRruleStringsEquivalent('FREQ=DAILY', (string) $results[0]['rrule']);
        $this->assertRruleStringsEquivalent('FREQ=WEEKLY;BYDAY=MO', (string) $results[1]['rrule']);

        // Both should share the same DateTimeContext
        $this->assertEquals(
            $results[0]['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s'),
            $results[1]['dateTimeContext']->getDateTime()->format('Y-m-d H:i:s')
        );
    }

    public function testGeneratesOccurrencesWithContext(): void
    {
        $component = new Component('VEVENT', [
            new Property('DTSTART', '20250107T100000Z'),
            new Property('RRULE', 'FREQ=DAILY;COUNT=3'),
            new Property('SUMMARY', 'Daily Event'),
        ]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);
        $this->assertCount(1, $results);

        $result = $results[0];
        $occurrences = $integrator->generateOccurrences(
            $result['rrule'],
            $result['dateTimeContext'],
            new \DateTimeImmutable('2025-01-07'),
            new \DateTimeImmutable('2025-01-10')
        );

        $this->assertCount(3, $occurrences);
        $this->assertEquals('2025-01-07 10:00:00', $occurrences[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-08 10:00:00', $occurrences[1]->format('Y-m-d H:i:s'));
        $this->assertEquals('2025-01-09 10:00:00', $occurrences[2]->format('Y-m-d H:i:s'));
    }

    public function testGeneratesOccurrencesWithTimezone(): void
    {
        $component = new Component('VEVENT', [
            new Property('DTSTART', '20250107T150000', ['TZID' => 'America/New_York']),
            new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU;COUNT=2'),
            new Property('SUMMARY', 'Weekly NY Event'),
        ]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponent($component);
        $this->assertCount(1, $results);

        $result = $results[0];
        $occurrences = $integrator->generateOccurrences(
            $result['rrule'],
            $result['dateTimeContext'],
            new \DateTimeImmutable('2025-01-07'),
            new \DateTimeImmutable('2025-01-21')
        );

        $this->assertCount(2, $occurrences);
        // Should preserve timezone
        $this->assertEquals('America/New_York', $occurrences[0]->getTimezone()->getName());
        $this->assertEquals('America/New_York', $occurrences[1]->getTimezone()->getName());
    }

    public function testExtractsFromNestedComponents(): void
    {
        $vevent = new Component('VEVENT', [
            new Property('DTSTART', '20250107T100000Z'),
            new Property('RRULE', 'FREQ=DAILY'),
            new Property('SUMMARY', 'Nested Event'),
        ]);

        $vtodo = new Component('VTODO', [
            new Property('DUE', '20250107T180000Z'),
            new Property('RRULE', 'FREQ=WEEKLY;BYDAY=FR'),
            new Property('SUMMARY', 'Nested Task'),
        ]);

        $vcalendar = new Component('VCALENDAR', [
            new Property('VERSION', '2.0'),
        ], [$vevent, $vtodo]);

        $integrator = new RruleContextIntegrator();
        $results = $integrator->extractFromComponents([$vcalendar]);

        $this->assertCount(2, $results);

        // First result should be VEVENT
        $this->assertEquals(ComponentType::VEVENT, $results[0]['dateTimeContext']->getComponentType());
        $this->assertRruleStringsEquivalent('FREQ=DAILY', (string) $results[0]['rrule']);

        // Second result should be VTODO
        $this->assertEquals(ComponentType::VTODO, $results[1]['dateTimeContext']->getComponentType());
        $this->assertRruleStringsEquivalent('FREQ=WEEKLY;BYDAY=FR', (string) $results[1]['rrule']);
    }

    public function testBackwardCompatibilityWithExistingRruler(): void
    {
        // Test that we can still use the original Rruler API
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=DAILY;COUNT=3');

        $this->assertInstanceOf(Rrule::class, $rrule);
        $this->assertRruleStringsEquivalent('FREQ=DAILY;COUNT=3', (string) $rrule);

        // Original API should still work unchanged
        $generator = new \EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator();
        $occurrences = $generator->generateOccurrencesInRange(
            $rrule,
            new \DateTimeImmutable('2025-01-07 10:00:00'),
            new \DateTimeImmutable('2025-01-07 10:00:00'),
            new \DateTimeImmutable('2025-01-10 10:00:00')
        );

        $this->assertCount(3, iterator_to_array($occurrences));
    }

    public static function provideVEventWithRrule(): array
    {
        return [
            'VEVENT with daily RRULE' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T100000Z'),
                    new Property('RRULE', 'FREQ=DAILY;COUNT=5'),
                    new Property('SUMMARY', 'Daily Event'),
                ]),
                'FREQ=DAILY;COUNT=5',
                '2025-01-07 10:00:00',
                null, // UTC
            ],
            'VEVENT with weekly RRULE and timezone' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T150000', ['TZID' => 'America/New_York']),
                    new Property('RRULE', 'FREQ=WEEKLY;BYDAY=TU;INTERVAL=2'),
                    new Property('SUMMARY', 'Bi-weekly NY Event'),
                ]),
                'FREQ=WEEKLY;BYDAY=TU;INTERVAL=2',
                '2025-01-07 15:00:00',
                'America/New_York',
            ],
            'VEVENT with complex RRULE' => [
                new Component('VEVENT', [
                    new Property('DTSTART', '20250107T120000Z'),
                    new Property('RRULE', 'FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1'),
                    new Property('SUMMARY', 'First Monday Monthly'),
                ]),
                'FREQ=MONTHLY;BYDAY=1MO;BYSETPOS=1',
                '2025-01-07 12:00:00',
                null,
            ],
        ];
    }

    public static function provideVTodoWithRrule(): array
    {
        return [
            'VTODO with DUE and RRULE' => [
                new Component('VTODO', [
                    new Property('DUE', '20250107T235959Z'),
                    new Property('RRULE', 'FREQ=MONTHLY;BYMONTHDAY=31'),
                    new Property('SUMMARY', 'Monthly Report'),
                ]),
                'FREQ=MONTHLY;BYMONTHDAY=31',
                '2025-01-07 23:59:59',
                null,
            ],
            'VTODO with DTSTART fallback' => [
                new Component('VTODO', [
                    new Property('DTSTART', '20250107T090000', ['TZID' => 'Europe/London']),
                    new Property('RRULE', 'FREQ=WEEKLY;BYDAY=MO'),
                    new Property('SUMMARY', 'Weekly London Task'),
                ]),
                'FREQ=WEEKLY;BYDAY=MO',
                '2025-01-07 09:00:00',
                'Europe/London',
            ],
        ];
    }
}
