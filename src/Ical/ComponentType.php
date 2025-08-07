<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Represents iCalendar component types supported by the RFC 5545 Context Parser.
 *
 * This enum defines the component types that support recurrence rules (RRULE)
 * and provides utility methods for validation and component-specific behavior.
 */
enum ComponentType: string
{
    case VEVENT = 'VEVENT';
    case VTODO = 'VTODO';

    /**
     * Checks if this component type supports recurrence rules.
     *
     * @return bool True if the component supports RRULE
     */
    public function supportsRecurrence(): bool
    {
        return match ($this) {
            self::VEVENT, self::VTODO => true,
        };
    }

    /**
     * Gets a human-readable description of the component type.
     *
     * @return string The component description
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::VEVENT => 'Event',
            self::VTODO => 'Task/Todo',
        };
    }

    /**
     * Gets the primary date/time property name for this component type.
     *
     * @return string The property name (DTSTART for VEVENT, DUE for VTODO)
     */
    public function getDateTimePropertyName(): string
    {
        return match ($this) {
            self::VEVENT => 'DTSTART',
            self::VTODO => 'DUE',
        };
    }

    /**
     * Checks if this component type has an alternate date/time property.
     *
     * @return bool True if there's an alternate property
     */
    public function hasAlternateDateTimeProperty(): bool
    {
        return match ($this) {
            self::VEVENT => false,
            self::VTODO => true, // VTODO can use DTSTART as fallback
        };
    }

    /**
     * Gets the alternate date/time property name for this component type.
     *
     * @return string|null The alternate property name or null if none exists
     */
    public function getAlternateDateTimePropertyName(): ?string
    {
        return match ($this) {
            self::VEVENT => null,
            self::VTODO => 'DTSTART',
        };
    }

    /**
     * Checks if a component type string is supported.
     *
     * @param string $type The component type string (case-insensitive)
     * @return bool True if the type is supported
     */
    public static function isSupported(string $type): bool
    {
        if (empty($type)) {
            return false;
        }

        $upperType = strtoupper($type);

        return self::tryFrom($upperType) !== null;
    }

    /**
     * Attempts to create a ComponentType from a string with case-insensitive matching.
     *
     * @param string $type The component type string
     * @return ComponentType|null The ComponentType or null if not supported
     */
    public static function tryFromCaseInsensitive(string $type): ?ComponentType
    {
        if (empty($type)) {
            return null;
        }

        return self::tryFrom(strtoupper($type));
    }

    /**
     * Gets an array of all supported component type strings.
     *
     * @return array<string> Array of supported component type strings
     */
    public static function getSupportedTypes(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
