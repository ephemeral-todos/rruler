<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Represents an iCalendar component (VEVENT, VTODO, VCALENDAR, etc.).
 *
 * A component contains properties and can optionally contain child components.
 * This class provides methods to access properties by name and manage child
 * components in a hierarchical structure.
 */
final class Component
{
    /** @var array<Property> */
    private array $properties;

    /** @var array<Component> */
    private array $children;

    /**
     * @param string $type The component type (VEVENT, VTODO, etc.)
     * @param array<Property> $properties The component properties
     * @param array<Component> $children Child components
     */
    public function __construct(
        private readonly string $type,
        array $properties = [],
        array $children = [],
    ) {
        // Create defensive copies to ensure immutability
        $this->properties = array_values($properties);
        $this->children = array_values($children);
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<Property>
     */
    public function getProperties(): array
    {
        // Return a copy to maintain immutability
        return [...$this->properties];
    }

    /**
     * @return array<Component>
     */
    public function getChildren(): array
    {
        // Return a copy to maintain immutability
        return [...$this->children];
    }

    /**
     * Gets the first property with the specified name.
     *
     * @param string $name The property name (case-insensitive)
     * @return Property|null The property or null if not found
     */
    public function getProperty(string $name): ?Property
    {
        $upperName = strtoupper($name);

        foreach ($this->properties as $property) {
            if ($property->getName() === $upperName) {
                return $property;
            }
        }

        return null;
    }

    /**
     * Checks if a property with the specified name exists.
     *
     * @param string $name The property name (case-insensitive)
     * @return bool True if the property exists
     */
    public function hasProperty(string $name): bool
    {
        return $this->getProperty($name) !== null;
    }

    /**
     * Gets all properties with the specified name.
     *
     * @param string $name The property name (case-insensitive)
     * @return array<Property> Array of properties with the specified name
     */
    public function getPropertiesByName(string $name): array
    {
        $upperName = strtoupper($name);
        $matchingProperties = [];

        foreach ($this->properties as $property) {
            if ($property->getName() === $upperName) {
                $matchingProperties[] = $property;
            }
        }

        return $matchingProperties;
    }

    /**
     * Adds a property to this component.
     *
     * @param Property $property The property to add
     */
    public function addProperty(Property $property): void
    {
        $this->properties[] = $property;
    }

    /**
     * Adds a child component to this component.
     *
     * @param Component $child The child component to add
     */
    public function addChild(Component $child): void
    {
        $this->children[] = $child;
    }
}
