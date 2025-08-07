<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\ComponentType;
use EphemeralTodos\Rruler\Ical\DateTimeContext;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeContextTest extends TestCase
{
    #[DataProvider('provideValidDateTimeContexts')]
    public function testCreatesValidDateTimeContext(
        \DateTimeImmutable $dateTime,
        ?string $timezone,
        ComponentType $componentType,
        ?string $originalValue,
    ): void {
        $context = new DateTimeContext($dateTime, $timezone, $componentType, $originalValue);

        $this->assertEquals($dateTime, $context->getDateTime());
        $this->assertEquals($timezone, $context->getTimezone());
        $this->assertEquals($componentType, $context->getComponentType());
        $this->assertEquals($originalValue, $context->getOriginalValue());
    }

    public function testCreateWithoutOptionalParameters(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $componentType = ComponentType::VEVENT;

        $context = new DateTimeContext($dateTime, null, $componentType);

        $this->assertEquals($dateTime, $context->getDateTime());
        $this->assertNull($context->getTimezone());
        $this->assertEquals($componentType, $context->getComponentType());
        $this->assertNull($context->getOriginalValue());
    }

    public function testHasTimezone(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:00:00');

        $contextWithTimezone = new DateTimeContext(
            $dateTime,
            'America/New_York',
            ComponentType::VEVENT
        );

        $contextWithoutTimezone = new DateTimeContext(
            $dateTime,
            null,
            ComponentType::VEVENT
        );

        $this->assertTrue($contextWithTimezone->hasTimezone());
        $this->assertFalse($contextWithoutTimezone->hasTimezone());
    }

    public function testIsUtc(): void
    {
        $utcDateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $localDateTime = new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York'));

        $utcContext = new DateTimeContext($utcDateTime, null, ComponentType::VEVENT);
        $localContext = new DateTimeContext($localDateTime, 'America/New_York', ComponentType::VEVENT);

        $this->assertTrue($utcContext->isUtc());
        $this->assertFalse($localContext->isUtc());
    }

    public function testIsFloating(): void
    {
        // Create a truly floating time by creating DateTime with a non-UTC timezone
        // but passing null as timezone context (indicating it's floating)
        $tempTimezone = new \DateTimeZone('America/New_York');
        $floatingDateTime = new \DateTimeImmutable('2025-01-07 10:00:00', $tempTimezone);
        $utcDateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $zonedDateTime = new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York'));

        // Floating: has a DateTime with timezone but no timezone context (null)
        $floatingContext = new DateTimeContext($floatingDateTime, null, ComponentType::VEVENT);
        // UTC: explicitly UTC DateTime and null timezone context
        $utcContext = new DateTimeContext($utcDateTime, null, ComponentType::VEVENT);
        // Zoned: has both DateTime timezone and timezone context
        $zonedContext = new DateTimeContext($zonedDateTime, 'America/New_York', ComponentType::VEVENT);

        $this->assertTrue($floatingContext->isFloating());
        $this->assertFalse($utcContext->isFloating());
        $this->assertFalse($zonedContext->isFloating());
    }

    public function testFormatForRrule(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:30:45 UTC');
        $context = new DateTimeContext($dateTime, null, ComponentType::VEVENT);

        $formatted = $context->formatForRrule();

        $this->assertEquals('20250107T103045Z', $formatted);
    }

    public function testFormatForRruleWithLocalTime(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:30:45');
        $context = new DateTimeContext($dateTime, 'America/New_York', ComponentType::VEVENT);

        $formatted = $context->formatForRrule();

        $this->assertEquals('20250107T103045', $formatted);
    }

    public function testToUtc(): void
    {
        $localDateTime = new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York'));
        $context = new DateTimeContext($localDateTime, 'America/New_York', ComponentType::VEVENT);

        $utcContext = $context->toUtc();

        $this->assertTrue($utcContext->isUtc());
        $this->assertEquals(ComponentType::VEVENT, $utcContext->getComponentType());
        $this->assertNull($utcContext->getTimezone());
        // New York is UTC-5 in January, so 10:00 EST = 15:00 UTC
        $this->assertEquals('15:00:00', $utcContext->getDateTime()->format('H:i:s'));
    }

    public function testToUtcWithAlreadyUtcDateTime(): void
    {
        $utcDateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $context = new DateTimeContext($utcDateTime, null, ComponentType::VEVENT);

        $utcContext = $context->toUtc();

        // Should return same context if already UTC
        $this->assertEquals($context->getDateTime(), $utcContext->getDateTime());
        $this->assertTrue($utcContext->isUtc());
    }

    public function testToTimezone(): void
    {
        $utcDateTime = new \DateTimeImmutable('2025-01-07 15:00:00 UTC');
        $context = new DateTimeContext($utcDateTime, null, ComponentType::VEVENT);

        $nyContext = $context->toTimezone('America/New_York');

        $this->assertEquals('America/New_York', $nyContext->getTimezone());
        $this->assertEquals('America/New_York', $nyContext->getDateTime()->getTimezone()->getName());
        $this->assertEquals(ComponentType::VEVENT, $nyContext->getComponentType());
        // 15:00 UTC = 10:00 EST in January
        $this->assertEquals('10:00:00', $nyContext->getDateTime()->format('H:i:s'));
    }

    public function testToTimezoneWithSameTimezone(): void
    {
        $nyDateTime = new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York'));
        $context = new DateTimeContext($nyDateTime, 'America/New_York', ComponentType::VEVENT);

        $sameContext = $context->toTimezone('America/New_York');

        // Should return equivalent context with same timezone
        $this->assertEquals($context->getDateTime()->format('Y-m-d H:i:s'), $sameContext->getDateTime()->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $sameContext->getTimezone());
    }

    public function testWithComponentType(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $eventContext = new DateTimeContext($dateTime, null, ComponentType::VEVENT);

        $todoContext = $eventContext->withComponentType(ComponentType::VTODO);

        $this->assertEquals(ComponentType::VTODO, $todoContext->getComponentType());
        $this->assertEquals($dateTime, $todoContext->getDateTime());
        $this->assertNull($todoContext->getTimezone());
    }

    public function testWithOriginalValue(): void
    {
        $dateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $context = new DateTimeContext($dateTime, null, ComponentType::VEVENT);

        $contextWithValue = $context->withOriginalValue('20250107T100000Z');

        $this->assertEquals('20250107T100000Z', $contextWithValue->getOriginalValue());
        $this->assertEquals($dateTime, $contextWithValue->getDateTime());
        $this->assertEquals(ComponentType::VEVENT, $contextWithValue->getComponentType());
    }

    public function testImmutability(): void
    {
        $originalDateTime = new \DateTimeImmutable('2025-01-07 10:00:00 UTC');
        $context = new DateTimeContext($originalDateTime, null, ComponentType::VEVENT);

        $utcContext = $context->toUtc();
        $nyContext = $context->toTimezone('America/New_York');
        $todoContext = $context->withComponentType(ComponentType::VTODO);

        // Original context should be unchanged
        $this->assertEquals($originalDateTime, $context->getDateTime());
        $this->assertEquals(ComponentType::VEVENT, $context->getComponentType());
        $this->assertNull($context->getTimezone());

        // All transformations should create new instances
        $this->assertNotSame($context, $utcContext);
        $this->assertNotSame($context, $nyContext);
        $this->assertNotSame($context, $todoContext);
    }

    public static function provideValidDateTimeContexts(): array
    {
        return [
            'UTC event with original value' => [
                new \DateTimeImmutable('2025-01-07 10:00:00 UTC'),
                null,
                ComponentType::VEVENT,
                '20250107T100000Z',
            ],
            'New York event' => [
                new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York')),
                'America/New_York',
                ComponentType::VEVENT,
                '20250107T100000',
            ],
            'VTODO with DUE' => [
                new \DateTimeImmutable('2025-01-07 23:59:59 UTC'),
                null,
                ComponentType::VTODO,
                '20250107T235959Z',
            ],
            'Floating time VEVENT' => [
                new \DateTimeImmutable('2025-01-07 10:00:00'),
                null,
                ComponentType::VEVENT,
                '20250107T100000',
            ],
            'London event' => [
                new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('Europe/London')),
                'Europe/London',
                ComponentType::VEVENT,
                null,
            ],
        ];
    }
}
