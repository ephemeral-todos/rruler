<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

use EphemeralTodos\Rruler\Rrule;
use EphemeralTodos\Rruler\Rruler;

/**
 * Main iCalendar parser that combines all parsing components to extract
 * RRULE and DateTimeContext information from complete iCalendar data.
 *
 * This class serves as the main entry point for parsing iCalendar files
 * and extracting recurrence-related information from VEVENT and VTODO components.
 */
final class IcalParser
{
    private readonly LineUnfolder $lineUnfolder;
    private readonly PropertyParser $propertyParser;
    private readonly ComponentExtractor $componentExtractor;
    private readonly Rruler $rruler;

    public function __construct(
        ?LineUnfolder $lineUnfolder = null,
        ?PropertyParser $propertyParser = null,
        ?ComponentExtractor $componentExtractor = null,
        ?Rruler $rruler = null,
    ) {
        $this->lineUnfolder = $lineUnfolder ?? new LineUnfolder();
        $this->propertyParser = $propertyParser ?? new PropertyParser();
        $this->componentExtractor = $componentExtractor ?? new ComponentExtractor();
        $this->rruler = $rruler ?? new Rruler();
    }

    /**
     * Parse complete iCalendar data and extract RRULE contexts.
     *
     * @param string $icalData Complete iCalendar data string
     * @return array<array{component: Component, dateTimeContext: DateTimeContext, rrule?: Rrule}> Parsed components with context
     */
    public function parse(string $icalData): array
    {
        if (trim($icalData) === '') {
            return [];
        }

        try {
            // Step 1: Unfold lines according to RFC 5545
            $unfolded = $this->lineUnfolder->unfold($icalData);

            // Step 2: Parse properties from unfolded lines
            $properties = $this->parseAllProperties($unfolded);

            // Step 3: Extract components (VEVENT, VTODO) from properties
            $components = $this->componentExtractor->extract($properties);

            // Step 4: Filter for relevant component types only (including nested)
            $relevantComponents = $this->findRelevantComponents($components);

            // Step 5: Extract RRULE and DateTimeContext from each relevant component
            $results = [];
            foreach ($relevantComponents as $component) {
                $result = $this->extractComponentContext($component);
                if ($result !== null) {
                    $results[] = $result;
                }
            }

            return $results;
        } catch (\Exception $e) {
            // Return empty array for malformed data rather than throwing
            return [];
        }
    }

    /**
     * Find relevant components (VEVENT, VTODO) including nested components.
     *
     * @param array<Component> $components All extracted components
     * @return array<Component> Relevant components found at any level
     */
    private function findRelevantComponents(array $components): array
    {
        $relevant = [];

        foreach ($components as $component) {
            // Check if this component is relevant
            if (in_array($component->getType(), ['VEVENT', 'VTODO'], true)) {
                $relevant[] = $component;
            }

            // Recursively check child components
            $childRelevant = $this->findRelevantComponents($component->getChildren());
            $relevant = array_merge($relevant, $childRelevant);
        }

        return $relevant;
    }

    /**
     * Extract context information from a single component.
     *
     * @param Component $component The component to process
     * @return array{component: Component, dateTimeContext: DateTimeContext, rrule?: Rrule}|null Context data or null if extraction fails
     */
    private function extractComponentContext(Component $component): ?array
    {
        // Extract DateTimeContext first (required)
        $dateTimeExtractor = new DateTimeContextExtractor();
        $dateTimeContext = $dateTimeExtractor->extractFromComponent($component);

        if ($dateTimeContext === null) {
            // No valid date/time context means we can't process this component
            return null;
        }

        // Build basic result
        $result = [
            'component' => $component,
            'dateTimeContext' => $dateTimeContext,
        ];

        // Try to extract RRULE (optional)
        $rruleExtractor = new RruleExtractor();
        $rruleData = $rruleExtractor->extractFromComponent($component);

        if (!empty($rruleData)) {
            // Use the first RRULE if multiple exist
            $firstRrule = $rruleData[0];

            try {
                $rrule = $this->rruler->parse($firstRrule['rrule']);
                $result['rrule'] = $rrule;
            } catch (\Exception $e) {
                // Skip invalid RRULEs but continue with the component
            }
        }

        return $result;
    }

    /**
     * Parse all properties from unfolded iCalendar data.
     *
     * @param string $unfoldedData Unfolded iCalendar data
     * @return array<Property> Parsed properties
     */
    private function parseAllProperties(string $unfoldedData): array
    {
        $properties = [];
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $unfoldedData));

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            try {
                $property = $this->propertyParser->parse($line);
                $properties[] = $property;
            } catch (\Exception $e) {
                // Skip malformed property lines but continue processing
                continue;
            }
        }

        return $properties;
    }
}
