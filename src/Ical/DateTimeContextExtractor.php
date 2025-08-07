<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Extracts DateTimeContext objects from iCalendar components.
 *
 * This class handles extraction of DTSTART (for VEVENT) and DUE (for VTODO)
 * properties with proper timezone handling and fallback logic.
 */
final class DateTimeContextExtractor
{
    private readonly DateTimeParser $dateTimeParser;

    public function __construct(?DateTimeParser $dateTimeParser = null)
    {
        $this->dateTimeParser = $dateTimeParser ?? new DateTimeParser();
    }

    /**
     * Extracts DateTimeContext from a single component.
     *
     * @param Component $component The component to extract from
     * @return DateTimeContext|null The extracted context or null if no suitable property found
     */
    public function extractFromComponent(Component $component): ?DateTimeContext
    {
        $componentType = ComponentType::tryFromCaseInsensitive($component->getType());

        if ($componentType === null) {
            return null; // Unsupported component type
        }

        // Get the primary date/time property for this component type
        $primaryProperty = $component->getProperty($componentType->getDateTimePropertyName());

        if ($primaryProperty !== null) {
            return $this->createContextFromProperty($primaryProperty, $componentType);
        }

        // Try alternate property if available (e.g., DTSTART for VTODO)
        if ($componentType->hasAlternateDateTimeProperty()) {
            $alternatePropertyName = $componentType->getAlternateDateTimePropertyName();
            if ($alternatePropertyName !== null) {
                $alternateProperty = $component->getProperty($alternatePropertyName);
                if ($alternateProperty !== null) {
                    return $this->createContextFromProperty($alternateProperty, $componentType);
                }
            }
        }

        return null;
    }

    /**
     * Extracts DateTimeContext objects from multiple components.
     *
     * @param array<Component> $components The components to extract from
     * @return array<DateTimeContext> Array of extracted contexts
     */
    public function extractFromComponents(array $components): array
    {
        $contexts = [];

        foreach ($components as $component) {
            // Extract from this component
            $context = $this->extractFromComponent($component);
            if ($context !== null) {
                $contexts[] = $context;
            }

            // Recursively extract from child components
            foreach ($component->getChildren() as $child) {
                $childContexts = $this->extractFromComponents([$child]);
                $contexts = array_merge($contexts, $childContexts);
            }
        }

        return $contexts;
    }

    /**
     * Creates a DateTimeContext from a property.
     *
     * @param Property $property The date/time property
     * @param ComponentType $componentType The component type
     * @return DateTimeContext The created context
     */
    private function createContextFromProperty(Property $property, ComponentType $componentType): DateTimeContext
    {
        $value = $property->getValue();
        $timezone = $property->getParameter('TZID');

        if ($timezone !== null) {
            // Parse with specific timezone
            $dateTime = $this->dateTimeParser->parseWithTimezone($value, $timezone);
        } else {
            // Parse without timezone (UTC or floating)
            $dateTime = $this->dateTimeParser->parse($value);
        }

        return new DateTimeContext(
            $dateTime,
            $timezone,
            $componentType,
            $value
        );
    }
}
