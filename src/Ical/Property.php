<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Represents an iCalendar property with its name, value, and parameters.
 *
 * This is an immutable value object that holds the parsed components
 * of an iCalendar property line according to RFC 5545.
 */
final class Property
{
    /**
     * @param string $name The property name (always uppercase)
     * @param string $value The property value
     * @param array<string, string> $parameters The property parameters (keys are uppercase)
     */
    public function __construct(
        private readonly string $name,
        private readonly string $value,
        private readonly array $parameters = [],
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return array<string, string>
     */
    public function getParameters(): array
    {
        // Return a copy to maintain immutability
        return $this->parameters;
    }

    public function getParameter(string $name, ?string $default = null): ?string
    {
        $upperName = strtoupper($name);

        return $this->parameters[$upperName] ?? $default;
    }

    public function hasParameter(string $name): bool
    {
        $upperName = strtoupper($name);

        return isset($this->parameters[$upperName]);
    }
}
