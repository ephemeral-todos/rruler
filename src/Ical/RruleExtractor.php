<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Extracts RRULE strings and associated context from iCalendar components.
 *
 * This class searches through components to find RRULE properties and
 * extracts them along with relevant context information like DTSTART,
 * SUMMARY, and component type.
 */
final class RruleExtractor
{
    /**
     * Extracts RRULE data from a single component.
     *
     * @param Component $component The component to extract from
     * @return array<array{rrule: string, component_type: string, dtstart?: string, summary?: string}> Array of RRULE data
     */
    public function extractFromComponent(Component $component): array
    {
        $rrules = [];

        // Get all RRULE properties from this component
        $rruleProperties = $component->getPropertiesByName('RRULE');

        if (empty($rruleProperties)) {
            // Check child components recursively
            foreach ($component->getChildren() as $child) {
                $rrules = array_merge($rrules, $this->extractFromComponent($child));
            }

            return $rrules;
        }

        // Extract context information once for all RRULEs in this component
        $context = $this->extractContext($component);

        // Create an entry for each RRULE
        foreach ($rruleProperties as $rruleProperty) {
            $rruleData = [
                'rrule' => $rruleProperty->getValue(),
                'component_type' => $component->getType(),
            ];

            // Add context information if available
            if ($context['dtstart'] !== null) {
                $rruleData['dtstart'] = $context['dtstart'];
            }
            if ($context['summary'] !== null) {
                $rruleData['summary'] = $context['summary'];
            }
            if ($context['due'] !== null) {
                $rruleData['due'] = $context['due'];
            }

            $rrules[] = $rruleData;
        }

        // Also check child components recursively
        foreach ($component->getChildren() as $child) {
            $rrules = array_merge($rrules, $this->extractFromComponent($child));
        }

        return $rrules;
    }

    /**
     * Extracts RRULE data from multiple components.
     *
     * @param array<Component> $components The components to extract from
     * @return array<array{rrule: string, component_type: string, dtstart?: string, summary?: string}> Array of RRULE data
     */
    public function extractFromComponents(array $components): array
    {
        $rrules = [];

        foreach ($components as $component) {
            $rrules = array_merge($rrules, $this->extractFromComponent($component));
        }

        return $rrules;
    }

    /**
     * Extracts context information from a component.
     *
     * @param Component $component The component to extract context from
     * @return array{dtstart: string|null, summary: string|null, due: string|null} Context information
     */
    private function extractContext(Component $component): array
    {
        $context = [
            'dtstart' => null,
            'summary' => null,
            'due' => null,
        ];

        // Extract DTSTART
        $dtStartProperty = $component->getProperty('DTSTART');
        if ($dtStartProperty !== null) {
            $context['dtstart'] = $dtStartProperty->getValue();
        }

        // Extract SUMMARY
        $summaryProperty = $component->getProperty('SUMMARY');
        if ($summaryProperty !== null) {
            $context['summary'] = $summaryProperty->getValue();
        }

        // Extract DUE (for VTODO components)
        $dueProperty = $component->getProperty('DUE');
        if ($dueProperty !== null) {
            $context['due'] = $dueProperty->getValue();
        }

        return $context;
    }
}
