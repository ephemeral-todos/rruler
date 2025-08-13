<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\ComponentType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ComponentTypeTest extends TestCase
{
    #[DataProvider('provideValidComponentTypes')]
    public function testCreatesValidComponentTypes(string $value, ComponentType $expectedType): void
    {
        $componentType = ComponentType::from($value);

        $this->assertEquals($expectedType, $componentType);
        $this->assertEquals($value, $componentType->value);
    }

    #[DataProvider('provideInvalidComponentTypes')]
    public function testRejectsInvalidComponentTypes(string $value): void
    {
        $this->expectException(\ValueError::class);

        ComponentType::from($value);
    }

    #[DataProvider('provideValidComponentTypes')]
    public function testTryFromWithValidTypes(string $value, ComponentType $expectedType): void
    {
        $componentType = ComponentType::tryFrom($value);

        $this->assertEquals($expectedType, $componentType);
    }

    #[DataProvider('provideInvalidComponentTypes')]
    public function testTryFromWithInvalidTypesReturnsNull(string $value): void
    {
        $componentType = ComponentType::tryFrom($value);

        $this->assertNull($componentType);
    }

    /**
     * Test component type support validation.
     *
     * Tests comprehensive component type support behavior including recurrence support,
     * type validation, and case sensitivity handling.
     */
    public function testComponentTypeSupportValidation(): void
    {
        $supportValidationScenarios = [
            // Test recurrence support
            [
                'type' => 'recurrence_support',
                'test' => fn () => $this->assertTrue(ComponentType::VEVENT->supportsRecurrence()),
                'description' => 'VEVENT supports recurrence',
            ],
            [
                'type' => 'recurrence_support',
                'test' => fn () => $this->assertTrue(ComponentType::VTODO->supportsRecurrence()),
                'description' => 'VTODO supports recurrence',
            ],

            // Test supported types validation
            [
                'type' => 'supported_validation',
                'test' => fn () => $this->assertTrue(ComponentType::isSupported('VEVENT')),
                'description' => 'VEVENT is supported',
            ],
            [
                'type' => 'supported_validation',
                'test' => fn () => $this->assertTrue(ComponentType::isSupported('VTODO')),
                'description' => 'VTODO is supported',
            ],

            // Test unsupported types
            [
                'type' => 'unsupported_validation',
                'test' => fn () => $this->assertFalse(ComponentType::isSupported('VJOURNAL')),
                'description' => 'VJOURNAL is not supported',
            ],
            [
                'type' => 'unsupported_validation',
                'test' => fn () => $this->assertFalse(ComponentType::isSupported('VFREEBUSY')),
                'description' => 'VFREEBUSY is not supported',
            ],
            [
                'type' => 'unsupported_validation',
                'test' => fn () => $this->assertFalse(ComponentType::isSupported('INVALID')),
                'description' => 'Invalid type is not supported',
            ],
            [
                'type' => 'unsupported_validation',
                'test' => fn () => $this->assertFalse(ComponentType::isSupported('')),
                'description' => 'Empty type is not supported',
            ],

            // Test case insensitive support
            [
                'type' => 'case_insensitive',
                'test' => fn () => $this->assertTrue(ComponentType::isSupported('vevent')),
                'description' => 'Lowercase VEVENT is supported',
            ],
            [
                'type' => 'case_insensitive',
                'test' => fn () => $this->assertTrue(ComponentType::isSupported('VeVeNt')),
                'description' => 'Mixed case VEVENT is supported',
            ],
        ];

        foreach ($supportValidationScenarios as $scenario) {
            try {
                $scenario['test']();
            } catch (\Throwable $e) {
                $this->fail("Scenario '{$scenario['description']}' failed: ".$e->getMessage());
            }
        }
    }

    public function testFromIsCaseSensitive(): void
    {
        // Should work with exact case
        $this->assertEquals(ComponentType::VEVENT, ComponentType::from('VEVENT'));
        $this->assertEquals(ComponentType::VTODO, ComponentType::from('VTODO'));

        // Should fail with wrong case
        $this->expectException(\ValueError::class);
        ComponentType::from('vevent');
    }

    public function testTryFromWithCaseInsensitiveSupport(): void
    {
        $veventUpper = ComponentType::tryFromCaseInsensitive('VEVENT');
        $veventLower = ComponentType::tryFromCaseInsensitive('vevent');
        $veventMixed = ComponentType::tryFromCaseInsensitive('VeVeNt');

        $this->assertEquals(ComponentType::VEVENT, $veventUpper);
        $this->assertEquals(ComponentType::VEVENT, $veventLower);
        $this->assertEquals(ComponentType::VEVENT, $veventMixed);

        $vtodoUpper = ComponentType::tryFromCaseInsensitive('VTODO');
        $vtodoLower = ComponentType::tryFromCaseInsensitive('vtodo');
        $vtodoMixed = ComponentType::tryFromCaseInsensitive('VtOdO');

        $this->assertEquals(ComponentType::VTODO, $vtodoUpper);
        $this->assertEquals(ComponentType::VTODO, $vtodoLower);
        $this->assertEquals(ComponentType::VTODO, $vtodoMixed);
    }

    public function testTryFromCaseInsensitiveReturnsNullForInvalidTypes(): void
    {
        $this->assertNull(ComponentType::tryFromCaseInsensitive('VJOURNAL'));
        $this->assertNull(ComponentType::tryFromCaseInsensitive('invalid'));
        $this->assertNull(ComponentType::tryFromCaseInsensitive(''));
    }

    public function testGetSupportedTypes(): void
    {
        $supportedTypes = ComponentType::getSupportedTypes();

        $this->assertIsArray($supportedTypes);
        $this->assertContains('VEVENT', $supportedTypes);
        $this->assertContains('VTODO', $supportedTypes);
        $this->assertCount(2, $supportedTypes);
    }

    public function testGetDescription(): void
    {
        $this->assertEquals('Event', ComponentType::VEVENT->getDescription());
        $this->assertEquals('Task/Todo', ComponentType::VTODO->getDescription());
    }

    public function testGetDateTimePropertyName(): void
    {
        $this->assertEquals('DTSTART', ComponentType::VEVENT->getDateTimePropertyName());
        $this->assertEquals('DUE', ComponentType::VTODO->getDateTimePropertyName());
    }

    public function testHasAlternateDateTimeProperty(): void
    {
        $this->assertFalse(ComponentType::VEVENT->hasAlternateDateTimeProperty());
        $this->assertTrue(ComponentType::VTODO->hasAlternateDateTimeProperty());
    }

    public function testGetAlternateDateTimePropertyName(): void
    {
        $this->assertNull(ComponentType::VEVENT->getAlternateDateTimePropertyName());
        $this->assertEquals('DTSTART', ComponentType::VTODO->getAlternateDateTimePropertyName());
    }

    public static function provideValidComponentTypes(): array
    {
        return [
            'VEVENT' => ['VEVENT', ComponentType::VEVENT],
            'VTODO' => ['VTODO', ComponentType::VTODO],
        ];
    }

    public static function provideInvalidComponentTypes(): array
    {
        return [
            'empty string' => [''],
            'VJOURNAL' => ['VJOURNAL'],
            'VFREEBUSY' => ['VFREEBUSY'],
            'VCALENDAR' => ['VCALENDAR'],
            'VTIMEZONE' => ['VTIMEZONE'],
            'VALARM' => ['VALARM'],
            'lowercase vevent' => ['vevent'],
            'lowercase vtodo' => ['vtodo'],
            'mixed case VeVeNt' => ['VeVeNt'],
            'invalid' => ['INVALID'],
            'numeric' => ['123'],
            'special chars' => ['V@EVENT'],
        ];
    }
}
