<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Extracts iCalendar components from a flat array of properties.
 *
 * This class processes BEGIN/END property pairs to build hierarchical
 * component structures. It handles nested components (like VEVENT inside
 * VCALENDAR) and validates proper nesting structure.
 */
final class ComponentExtractor
{
    /**
     * Extracts components from an array of properties.
     *
     * @param array<Property> $properties The properties to process
     * @return array<Component> The extracted components
     *
     * @throws \InvalidArgumentException If component structure is invalid
     */
    public function extract(array $properties): array
    {
        if (empty($properties)) {
            return [];
        }

        $components = [];
        $stack = []; // Stack to handle nested components
        $currentProperties = []; // Properties for the current component

        foreach ($properties as $property) {
            $propertyName = $property->getName();

            if ($propertyName === 'BEGIN') {
                // Starting a new component
                $componentType = $property->getValue();

                // If we're already inside a component, save current properties
                if (!empty($stack)) {
                    $stack[count($stack) - 1]['properties'] = $currentProperties;
                    $currentProperties = [];
                }

                // Push new component onto stack
                $stack[] = [
                    'type' => $componentType,
                    'properties' => [],
                    'children' => [],
                ];
            } elseif ($propertyName === 'END') {
                // Ending a component
                $componentType = $property->getValue();

                if (empty($stack)) {
                    throw new \InvalidArgumentException('Unmatched END component: '.$componentType);
                }

                $current = array_pop($stack);

                if ($current['type'] !== $componentType) {
                    throw new \InvalidArgumentException(
                        'Mismatched component types: expected '.$current['type'].', got '.$componentType
                    );
                }

                // Add current properties to the component
                $current['properties'] = $currentProperties;
                $currentProperties = [];

                // Create the component
                $component = new Component(
                    $current['type'],
                    $current['properties'],
                    $current['children']
                );

                if (empty($stack)) {
                    // Top-level component
                    $components[] = $component;
                } else {
                    // Child component - add to parent
                    $stack[count($stack) - 1]['children'][] = $component;
                    // Restore parent properties
                    $currentProperties = $stack[count($stack) - 1]['properties'] ?? [];
                }
            } else {
                // Regular property - add to current component
                $currentProperties[] = $property;
            }
        }

        // Check for unmatched BEGIN components
        if (!empty($stack)) {
            $unmatched = array_map(fn ($item) => $item['type'], $stack);
            throw new \InvalidArgumentException(
                'Unmatched BEGIN component(s): '.implode(', ', $unmatched)
            );
        }

        return $components;
    }
}
