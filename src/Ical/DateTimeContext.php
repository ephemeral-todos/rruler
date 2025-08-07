<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Represents date/time context information for iCalendar components.
 *
 * This immutable value object contains a DateTime with associated timezone
 * information and component type context, providing utilities for RRULE
 * integration and timezone conversions.
 */
final class DateTimeContext
{
    /**
     * @param \DateTimeImmutable $dateTime The date/time value
     * @param string|null $timezone The timezone identifier (null for UTC or floating time)
     * @param ComponentType $componentType The component type this context belongs to
     * @param string|null $originalValue The original string value from iCalendar data
     */
    public function __construct(
        private readonly \DateTimeImmutable $dateTime,
        private readonly ?string $timezone,
        private readonly ComponentType $componentType,
        private readonly ?string $originalValue = null,
    ) {
    }

    /**
     * Gets the DateTime object.
     *
     * @return \DateTimeImmutable The date/time value
     */
    public function getDateTime(): \DateTimeImmutable
    {
        return $this->dateTime;
    }

    /**
     * Gets the timezone identifier.
     *
     * @return string|null The timezone identifier or null for UTC/floating time
     */
    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    /**
     * Gets the component type.
     *
     * @return ComponentType The component type
     */
    public function getComponentType(): ComponentType
    {
        return $this->componentType;
    }

    /**
     * Gets the original value from iCalendar data.
     *
     * @return string|null The original string value or null if not available
     */
    public function getOriginalValue(): ?string
    {
        return $this->originalValue;
    }

    /**
     * Checks if this context has timezone information.
     *
     * @return bool True if timezone is specified
     */
    public function hasTimezone(): bool
    {
        return $this->timezone !== null;
    }

    /**
     * Checks if this context represents UTC time.
     *
     * @return bool True if the DateTime is in UTC
     */
    public function isUtc(): bool
    {
        return $this->dateTime->getTimezone()->getName() === 'UTC';
    }

    /**
     * Checks if this context represents floating time (no timezone).
     *
     * @return bool True if this is floating time
     */
    public function isFloating(): bool
    {
        // Floating time has no timezone context AND is not explicitly UTC
        return $this->timezone === null && !$this->isUtc();
    }

    /**
     * Formats the DateTime for use in RRULE DTSTART parameter.
     *
     * @return string The formatted date/time string
     */
    public function formatForRrule(): string
    {
        // Only append 'Z' for truly UTC times, not for times with timezones
        if ($this->isUtc() && $this->timezone === null) {
            return $this->dateTime->format('Ymd\THis\Z');
        }

        return $this->dateTime->format('Ymd\THis');
    }

    /**
     * Converts this context to UTC time.
     *
     * @return DateTimeContext New context with UTC DateTime
     */
    public function toUtc(): DateTimeContext
    {
        // Always create a new instance for immutability, even if already UTC
        $utcDateTime = $this->dateTime->setTimezone(new \DateTimeZone('UTC'));

        return new DateTimeContext(
            $utcDateTime,
            null,
            $this->componentType,
            $this->originalValue
        );
    }

    /**
     * Converts this context to a specific timezone.
     *
     * @param string $timezone The target timezone identifier
     * @return DateTimeContext New context with converted DateTime
     */
    public function toTimezone(string $timezone): DateTimeContext
    {
        if ($this->timezone === $timezone) {
            return $this;
        }

        try {
            $targetTimezone = new \DateTimeZone($timezone);
            $convertedDateTime = $this->dateTime->setTimezone($targetTimezone);

            return new DateTimeContext(
                $convertedDateTime,
                $timezone,
                $this->componentType,
                $this->originalValue
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid timezone: '.$timezone, 0, $e);
        }
    }

    /**
     * Creates a new context with a different component type.
     *
     * @param ComponentType $componentType The new component type
     * @return DateTimeContext New context with updated component type
     */
    public function withComponentType(ComponentType $componentType): DateTimeContext
    {
        return new DateTimeContext(
            $this->dateTime,
            $this->timezone,
            $componentType,
            $this->originalValue
        );
    }

    /**
     * Creates a new context with an original value.
     *
     * @param string|null $originalValue The original value
     * @return DateTimeContext New context with updated original value
     */
    public function withOriginalValue(?string $originalValue): DateTimeContext
    {
        return new DateTimeContext(
            $this->dateTime,
            $this->timezone,
            $this->componentType,
            $originalValue
        );
    }
}
