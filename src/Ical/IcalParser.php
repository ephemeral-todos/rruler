<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

use EphemeralTodos\Rruler\Rrule;
use EphemeralTodos\Rruler\Rruler;

/**
 * Main iCalendar parser for extracting RRULE and datetime context from iCalendar data.
 *
 * The IcalParser provides a complete RFC 5545 compliant solution for parsing
 * iCalendar files and extracting recurrence rule information from VEVENT and
 * VTODO components. It serves as the high-level entry point that coordinates
 * multiple specialized parsing components to handle the complete parsing workflow.
 *
 * This parser is designed to handle real-world iCalendar data that may contain:
 * - Multiple calendar components (VEVENT, VTODO, VJOURNAL, etc.)
 * - Nested component structures (VALARM within VEVENT, etc.)
 * - Complex property formats with parameters and encoding
 * - Line folding and unfolding per RFC 5545
 * - Malformed or partially invalid data (graceful degradation)
 * - Mixed timezone and datetime formats
 *
 * Key features:
 * - Comprehensive iCalendar parsing with RFC 5545 compliance
 * - Robust error handling with graceful degradation
 * - Support for complex nested component structures
 * - Integration with Rruler for RRULE processing
 * - Extraction of datetime context for occurrence generation
 * - Filtering for relevant components (VEVENT, VTODO)
 * - Memory-efficient processing of large iCalendar files
 *
 * Parsing workflow:
 * 1. Unfold lines according to RFC 5545 line folding rules
 * 2. Parse individual properties with parameters and values
 * 3. Extract component hierarchy from parsed properties
 * 4. Filter for relevant component types (VEVENT, VTODO)
 * 5. Extract datetime context and RRULE data from each component
 * 6. Parse RRULE strings using integrated Rruler parser
 * 7. Return structured results with component, context, and RRULE data
 *
 * @example Basic iCalendar parsing
 * ```php
 * $parser = new IcalParser();
 * $icalData = file_get_contents('calendar.ics');
 * $results = $parser->parse($icalData);
 *
 * foreach ($results as $item) {
 *     $component = $item['component'];
 *     $context = $item['dateTimeContext'];
 *
 *     echo "Component: " . $component->getType() . "\n";
 *     echo "Start: " . $context->getStartDateTime()->format('Y-m-d H:i:s') . "\n";
 *
 *     if (isset($item['rrule'])) {
 *         echo "Recurrence: " . $item['rrule'] . "\n";
 *     }
 * }
 * ```
 * @example Processing recurring events
 * ```php
 * $parser = new IcalParser();
 * $results = $parser->parse($icalData);
 *
 * foreach ($results as $item) {
 *     if (isset($item['rrule'])) {
 *         $generator = new DefaultOccurrenceGenerator();
 *         $start = $item['dateTimeContext']->getStartDateTime();
 *
 *         foreach ($generator->generateOccurrences($item['rrule'], $start, 10) as $occurrence) {
 *             echo "Event occurs on: " . $occurrence->format('Y-m-d H:i:s') . "\n";
 *         }
 *     }
 * }
 * ```
 * @example Custom component integration
 * ```php
 * // Using custom parsing components for specialized needs
 * $lineUnfolder = new CustomLineUnfolder();
 * $propertyParser = new CustomPropertyParser();
 * $componentExtractor = new CustomComponentExtractor();
 * $rruler = new Rruler();
 *
 * $parser = new IcalParser($lineUnfolder, $propertyParser, $componentExtractor, $rruler);
 * $results = $parser->parse($icalData);
 * ```
 * @example Error-tolerant parsing
 * ```php
 * $parser = new IcalParser();
 *
 * // Parser handles malformed data gracefully
 * $malformedData = "BEGIN:VCALENDAR\nINVALID:LINE\nEND:VCALENDAR";
 * $results = $parser->parse($malformedData); // Returns empty array, doesn't throw
 *
 * echo count($results); // 0 - gracefully handled invalid data
 * ```
 *
 * @see Component For parsed component structure
 * @see DateTimeContext For datetime context extraction
 * @see RruleExtractor For RRULE extraction
 * @see Rruler For RRULE string parsing
 * @see https://tools.ietf.org/html/rfc5545 RFC 5545 iCalendar specification
 *
 * @author EphemeralTodos
 *
 * @since 1.0.0
 */
final class IcalParser
{
    private readonly LineUnfolder $lineUnfolder;
    private readonly PropertyParser $propertyParser;
    private readonly ComponentExtractor $componentExtractor;
    private readonly Rruler $rruler;

    /**
     * Creates a new IcalParser with optional custom parsing components.
     *
     * Allows injection of custom parsing components for specialized requirements
     * or testing purposes. All components default to their standard implementations
     * if not provided.
     *
     * @param LineUnfolder|null $lineUnfolder Optional line unfolding component for RFC 5545 line folding
     * @param PropertyParser|null $propertyParser Optional property parsing component for iCalendar properties
     * @param ComponentExtractor|null $componentExtractor Optional component extraction for iCalendar component hierarchy
     * @param Rruler|null $rruler Optional RRULE parser for recurrence rule processing
     *
     * @example Basic usage with defaults
     * ```php
     * $parser = new IcalParser();
     * ```
     * @example Custom component injection
     * ```php
     * $parser = new IcalParser(
     *     lineUnfolder: new CustomLineUnfolder(),
     *     propertyParser: new CustomPropertyParser(),
     *     componentExtractor: new CustomComponentExtractor(),
     *     rruler: new Rruler()
     * );
     * ```
     */
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
     * Parses complete iCalendar data and extracts RRULE contexts from relevant components.
     *
     * Processes a complete iCalendar string and returns structured information about
     * recurring events and tasks. The method handles the complete parsing pipeline
     * from raw iCalendar text to fully parsed RRULE objects with their associated
     * datetime contexts.
     *
     * The parser focuses on VEVENT and VTODO components that contain recurrence
     * information, extracting both the RRULE data and the necessary datetime
     * context for generating occurrences.
     *
     * Error handling approach:
     * - Returns empty array for completely invalid or empty input
     * - Skips malformed individual properties but continues processing
     * - Skips components without valid datetime context
     * - Skips invalid RRULE strings but preserves component and context
     * - Gracefully handles nested component structures
     *
     * @param string $icalData Complete iCalendar data string (RFC 5545 format)
     *                         May include multiple VCALENDAR blocks, components, properties
     * @return array<array{component: Component, dateTimeContext: DateTimeContext, rrule?: Rrule}>
     *                                                                                             Array of parsed results, each containing:
     *                                                                                             - component: The parsed Component object (VEVENT or VTODO)
     *                                                                                             - dateTimeContext: Extracted DateTimeContext with start/end times, timezone
     *                                                                                             - rrule: Optional parsed Rrule object (present only if valid RRULE found)
     *
     * @example Parse simple recurring event
     * ```php
     * $icalData = <<<ICAL
     * BEGIN:VCALENDAR
     * VERSION:2.0
     * PRODID:-//Example Corp//CalendarApp//EN
     * BEGIN:VEVENT
     * DTSTART:20240101T090000Z
     * DTEND:20240101T100000Z
     * RRULE:FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=10
     * SUMMARY:Team Meeting
     * END:VEVENT
     * END:VCALENDAR
     * ICAL;
     *
     * $parser = new IcalParser();
     * $results = $parser->parse($icalData);
     *
     * foreach ($results as $item) {
     *     echo "Found recurring " . $item['component']->getType() . "\n";
     *     echo "Starts: " . $item['dateTimeContext']->getStartDateTime()->format('Y-m-d H:i:s') . "\n";
     *     echo "Pattern: " . (string)$item['rrule'] . "\n";
     * }
     * ```
     * @example Handle multiple components
     * ```php
     * $results = $parser->parse($complexIcalData);
     *
     * $eventCount = 0;
     * $todoCount = 0;
     * $recurringCount = 0;
     *
     * foreach ($results as $item) {
     *     if ($item['component']->getType() === 'VEVENT') {
     *         $eventCount++;
     *     } elseif ($item['component']->getType() === 'VTODO') {
     *         $todoCount++;
     *     }
     *
     *     if (isset($item['rrule'])) {
     *         $recurringCount++;
     *     }
     * }
     *
     * echo "Found {$eventCount} events, {$todoCount} tasks, {$recurringCount} recurring\n";
     * ```
     * @example Error handling demonstration
     * ```php
     * // Malformed iCalendar data
     * $malformed = "BEGIN:VCALENDAR\nINVALID_PROPERTY\nEND:VCALENDAR";
     * $results = $parser->parse($malformed); // Returns [] gracefully
     *
     * // Empty input
     * $results = $parser->parse(''); // Returns []
     *
     * // Mixed valid/invalid content
     * $mixed = <<<ICAL
     * BEGIN:VCALENDAR
     * BEGIN:VEVENT
     * DTSTART:20240101T090000Z
     * INVALID:PROPERTY
     * RRULE:FREQ=DAILY;COUNT=5
     * END:VEVENT
     * END:VCALENDAR
     * ICAL;
     * $results = $parser->parse($mixed); // Skips invalid property, processes valid content
     * ```
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
