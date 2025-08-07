<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use EphemeralTodos\Rruler\Rruler;

/**
 * Integrates RRULE extraction with DateTimeContext for context-aware occurrence generation.
 *
 * This class combines the existing RruleExtractor and DateTimeContextExtractor
 * to provide a unified interface for extracting RRULE patterns with their
 * associated date/time context from iCalendar components.
 */
final class RruleContextIntegrator
{
    private readonly RruleExtractor $rruleExtractor;
    private readonly DateTimeContextExtractor $dateTimeExtractor;
    private readonly Rruler $rruler;

    public function __construct(
        ?RruleExtractor $rruleExtractor = null,
        ?DateTimeContextExtractor $dateTimeExtractor = null,
        ?Rruler $rruler = null,
    ) {
        $this->rruleExtractor = $rruleExtractor ?? new RruleExtractor();
        $this->dateTimeExtractor = $dateTimeExtractor ?? new DateTimeContextExtractor();
        $this->rruler = $rruler ?? new Rruler();
    }

    /**
     * Extracts RRULE and DateTimeContext pairs from a single component.
     *
     * @param Component $component The component to extract from
     * @return array<array{rrule: Rrule, dateTimeContext: DateTimeContext, component: Component}> Array of extracted data
     */
    public function extractFromComponent(Component $component): array
    {
        // First, extract the DateTimeContext
        $dateTimeContext = $this->dateTimeExtractor->extractFromComponent($component);
        if ($dateTimeContext === null) {
            return []; // No date/time context, can't generate meaningful occurrences
        }

        // Then extract RRULE data
        $rruleData = $this->rruleExtractor->extractFromComponent($component);
        if (empty($rruleData)) {
            return []; // No RRULEs found
        }

        $results = [];
        foreach ($rruleData as $rruleInfo) {
            // Parse the RRULE string into an Rrule object
            try {
                $rrule = $this->rruler->parse($rruleInfo['rrule']);

                $results[] = [
                    'rrule' => $rrule,
                    'dateTimeContext' => $dateTimeContext,
                    'component' => $component,
                ];
            } catch (\Exception $e) {
                // Skip invalid RRULEs but continue processing others
                continue;
            }
        }

        return $results;
    }

    /**
     * Extracts RRULE and DateTimeContext pairs from multiple components.
     *
     * @param array<Component> $components The components to extract from
     * @return array<array{rrule: Rrule, dateTimeContext: DateTimeContext, component: Component}> Array of extracted data
     */
    public function extractFromComponents(array $components): array
    {
        $results = [];

        foreach ($components as $component) {
            // Extract from this component
            $componentResults = $this->extractFromComponent($component);
            $results = array_merge($results, $componentResults);

            // Recursively extract from child components
            foreach ($component->getChildren() as $child) {
                $childResults = $this->extractFromComponents([$child]);
                $results = array_merge($results, $childResults);
            }
        }

        return $results;
    }

    /**
     * Generates occurrences using RRULE and DateTimeContext.
     *
     * @param Rrule $rrule The RRULE to use for generation
     * @param DateTimeContext $dateTimeContext The date/time context
     * @param \DateTimeImmutable $rangeStart The start of the occurrence range
     * @param \DateTimeImmutable $rangeEnd The end of the occurrence range
     * @return array<\DateTimeImmutable> Array of occurrence dates
     */
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeContext $dateTimeContext,
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEnd,
    ): array {
        // Use the DateTimeContext's DateTime as the DTSTART for occurrence generation
        $dtStart = $dateTimeContext->getDateTime();

        // Generate occurrences using DefaultOccurrenceGenerator directly
        $generator = new DefaultOccurrenceGenerator();
        $occurrences = $generator->generateOccurrencesInRange($rrule, $dtStart, $rangeStart, $rangeEnd);

        // Convert generator to array and ensure all occurrences maintain the original timezone
        $occurrenceArray = [];
        foreach ($occurrences as $occurrence) {
            // If the original context had a timezone, apply it to the occurrence
            if ($dateTimeContext->hasTimezone() && $dateTimeContext->getTimezone() !== null) {
                $timezone = new \DateTimeZone($dateTimeContext->getTimezone());
                $occurrence = $occurrence->setTimezone($timezone);
            }

            $occurrenceArray[] = $occurrence;
        }

        return $occurrenceArray;
    }

    /**
     * Convenience method to extract and generate occurrences in one step.
     *
     * @param Component $component The component containing RRULE and DTSTART/DUE
     * @param \DateTimeImmutable $rangeStart The start of the occurrence range
     * @param \DateTimeImmutable $rangeEnd The end of the occurrence range
     * @return array<array{rrule: Rrule, dateTimeContext: DateTimeContext, occurrences: array<\DateTimeImmutable>}> Results with occurrences
     */
    public function extractAndGenerateOccurrences(
        Component $component,
        \DateTimeImmutable $rangeStart,
        \DateTimeImmutable $rangeEnd,
    ): array {
        $extractedData = $this->extractFromComponent($component);
        $results = [];

        foreach ($extractedData as $data) {
            $occurrences = $this->generateOccurrences(
                $data['rrule'],
                $data['dateTimeContext'],
                $rangeStart,
                $rangeEnd
            );

            $results[] = [
                'rrule' => $data['rrule'],
                'dateTimeContext' => $data['dateTimeContext'],
                'occurrences' => $occurrences,
            ];
        }

        return $results;
    }
}
