<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Rrule;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class RruleTest extends TestCase
{
    #[DataProvider('provideValidRruleStrings')]
    public function testCreateFromValidRruleString(
        string $rruleString,
        string $expectedFrequency,
        ?int $expectedInterval,
        ?int $expectedCount,
        ?DateTimeImmutable $expectedUntil,
    ): void {
        $rrule = Rrule::fromString($rruleString);

        $this->assertEquals($expectedFrequency, $rrule->getFrequency());
        $this->assertEquals($expectedInterval ?? 1, $rrule->getInterval());
        $this->assertEquals($expectedCount, $rrule->getCount());
        $this->assertEquals($expectedUntil, $rrule->getUntil());
    }

    #[DataProvider('provideInvalidRruleStrings')]
    public function testCreateFromInvalidRruleString(string $expectedExceptionClass, string $expectedMessage, string $rruleString): void
    {
        $this->expectException($expectedExceptionClass);
        $this->expectExceptionMessage($expectedMessage);

        Rrule::fromString($rruleString);
    }

    public function testImmutability(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY;INTERVAL=2;COUNT=10');

        // Test that all properties are read-only (no setters)
        $reflection = new \ReflectionClass($rrule);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC);

        $setterMethods = array_filter($methods, fn ($method) => str_starts_with($method->getName(), 'set'));
        $this->assertEmpty($setterMethods, 'Rrule should not have any setter methods');
    }

    public function testStringRepresentation(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY;INTERVAL=2;COUNT=10');
        $stringRepresentation = (string) $rrule;

        $this->assertStringContainsString('FREQ=DAILY', $stringRepresentation);
        $this->assertStringContainsString('INTERVAL=2', $stringRepresentation);
        $this->assertStringContainsString('COUNT=10', $stringRepresentation);
    }

    public function testToStringProducesValidRrule(): void
    {
        $originalString = 'FREQ=WEEKLY;INTERVAL=3;COUNT=5';
        $rrule = Rrule::fromString($originalString);
        $recreatedRrule = Rrule::fromString((string) $rrule);

        $this->assertEquals($rrule->getFrequency(), $recreatedRrule->getFrequency());
        $this->assertEquals($rrule->getInterval(), $recreatedRrule->getInterval());
        $this->assertEquals($rrule->getCount(), $recreatedRrule->getCount());
        $this->assertEquals($rrule->getUntil(), $recreatedRrule->getUntil());
    }

    public function testDefaultValues(): void
    {
        $rrule = Rrule::fromString('FREQ=DAILY');

        $this->assertEquals('DAILY', $rrule->getFrequency());
        $this->assertEquals(1, $rrule->getInterval());
        $this->assertNull($rrule->getCount());
        $this->assertNull($rrule->getUntil());
    }

    public function testGettersReturnCorrectTypes(): void
    {
        $rruleWithCount = Rrule::fromString('FREQ=MONTHLY;INTERVAL=2;COUNT=15');
        $rruleWithUntil = Rrule::fromString('FREQ=WEEKLY;UNTIL=20251231T235959Z');

        $this->assertIsString($rruleWithCount->getFrequency());
        $this->assertIsInt($rruleWithCount->getInterval());
        $this->assertIsInt($rruleWithCount->getCount());
        $this->assertNull($rruleWithCount->getUntil());

        $this->assertIsString($rruleWithUntil->getFrequency());
        $this->assertIsInt($rruleWithUntil->getInterval());
        $this->assertNull($rruleWithUntil->getCount());
        $this->assertInstanceOf(DateTimeImmutable::class, $rruleWithUntil->getUntil());
    }

    public function testHasCountAndHasUntilMethods(): void
    {
        $rruleWithCount = Rrule::fromString('FREQ=DAILY;COUNT=10');
        $rruleWithUntil = Rrule::fromString('FREQ=DAILY;UNTIL=20251231T235959Z');
        $rruleWithNeither = Rrule::fromString('FREQ=DAILY');

        $this->assertTrue($rruleWithCount->hasCount());
        $this->assertFalse($rruleWithCount->hasUntil());

        $this->assertFalse($rruleWithUntil->hasCount());
        $this->assertTrue($rruleWithUntil->hasUntil());

        $this->assertFalse($rruleWithNeither->hasCount());
        $this->assertFalse($rruleWithNeither->hasUntil());
    }

    public function testToArrayRepresentation(): void
    {
        $rrule = Rrule::fromString('FREQ=WEEKLY;INTERVAL=2;COUNT=5');
        $array = $rrule->toArray();

        $expectedArray = [
            'freq' => 'WEEKLY',
            'interval' => 2,
            'count' => 5,
            'until' => null,
        ];

        $this->assertEquals($expectedArray, $array);
    }

    public static function provideValidRruleStrings(): array
    {
        return [
            'daily with defaults' => [
                'FREQ=DAILY',
                'DAILY',
                null, // interval will default to 1
                null, // count
                null,  // until
            ],
            'daily with interval' => [
                'FREQ=DAILY;INTERVAL=3',
                'DAILY',
                3,
                null,
                null,
            ],
            'weekly with count' => [
                'FREQ=WEEKLY;COUNT=15',
                'WEEKLY',
                null, // defaults to 1
                15,
                null,
            ],
            'monthly with until' => [
                'FREQ=MONTHLY;UNTIL=20251231T235959Z',
                'MONTHLY',
                null, // defaults to 1
                null,
                new DateTimeImmutable('2025-12-31T23:59:59Z'),
            ],
            'yearly with all parameters' => [
                'FREQ=YEARLY;INTERVAL=2;COUNT=10',
                'YEARLY',
                2,
                10,
                null,
            ],
            'case insensitive' => [
                'freq=daily;interval=5',
                'DAILY',
                5,
                null,
                null,
            ],
        ];
    }

    public static function provideInvalidRruleStrings(): array
    {
        return [
            'empty string' => [
                ParseException::class,
                'RRULE string cannot be empty',
                '',
            ],
            'missing frequency' => [
                ValidationException::class,
                'FREQ parameter is required',
                'INTERVAL=2;COUNT=10',
            ],
            'invalid frequency' => [
                ValidationException::class,
                'Invalid frequency value: INVALID. Valid values are: DAILY, WEEKLY, MONTHLY, YEARLY',
                'FREQ=INVALID',
            ],
            'count and until together' => [
                ValidationException::class,
                'COUNT and UNTIL are mutually exclusive',
                'FREQ=DAILY;COUNT=10;UNTIL=20251231T235959Z',
            ],
        ];
    }
}
