<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Compatibility;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;
use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Recur\EventIterator;

/**
 * Abstract base class for compatibility testing between Rruler and sabre/dav.
 *
 * This class provides utilities for comparing RRULE parsing and occurrence generation
 * results between our implementation and the sabre/vobject library.
 */
abstract class CompatibilityTestCase extends TestCase
{
    protected Rruler $rruler;
    protected DefaultOccurrenceGenerator $occurrenceGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rruler = new Rruler();
        $this->occurrenceGenerator = new DefaultOccurrenceGenerator();
    }

    /**
     * Parse an RRULE using Rruler and generate occurrences.
     *
     * @param string $rruleString The RRULE string to parse
     * @param DateTimeImmutable $start The start date for occurrence generation
     * @param int|null $limit Maximum number of occurrences to generate
     * @return array<DateTimeImmutable>
     */
    protected function getRrulerOccurrences(
        string $rruleString,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): array {
        $rrule = $this->rruler->parse($rruleString);

        // For compatibility testing, we need to simulate iCalendar behavior
        // where the start date (DTSTART) is always included as the first occurrence
        // This matches sabre/vobject's EventIterator behavior

        // Check if the start date would naturally be included by generating one occurrence
        $testOccurrences = iterator_to_array($this->occurrenceGenerator->generateOccurrences($rrule, $start, 1));
        $startDateMatches = !empty($testOccurrences) && $testOccurrences[0]->format('Y-m-d H:i:s') === $start->format('Y-m-d H:i:s');

        if ($startDateMatches) {
            // Start date naturally matches the RRULE, use normal generation
            $occurrences = $this->occurrenceGenerator->generateOccurrences($rrule, $start, $limit);

            return iterator_to_array($occurrences);
        } else {
            // Start date doesn't match RRULE, include it first then get subsequent matches
            $occurrences = [];
            $count = 0;

            // Always include the start date first (RFC 5545 iCalendar behavior)
            if ($limit === null || $count < $limit) {
                $occurrences[] = $start;
                ++$count;
            }

            // Generate subsequent occurrences
            if ($limit === null || $count < $limit) {
                $remainingLimit = ($limit !== null) ? $limit - $count : null;
                $subsequentOccurrences = $this->occurrenceGenerator->generateOccurrences($rrule, $start, $remainingLimit);

                foreach ($subsequentOccurrences as $occurrence) {
                    if ($limit !== null && $count >= $limit) {
                        break;
                    }

                    $occurrences[] = $occurrence;
                    ++$count;
                }
            }

            return $occurrences;
        }
    }

    /**
     * Parse an RRULE using sabre/vobject and generate occurrences.
     *
     * @param string $rruleString The RRULE string to parse
     * @param DateTimeImmutable $start The start date for occurrence generation
     * @param int|null $limit Maximum number of occurrences to generate
     * @return array<DateTimeImmutable>
     */
    protected function getSabreOccurrences(
        string $rruleString,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): array {
        // Create a minimal VEVENT with the RRULE
        $vcalendar = new VCalendar();
        $vevent = $vcalendar->add('VEVENT');
        $vevent->add('DTSTART', $start);
        $vevent->add('RRULE', $rruleString);
        $vevent->add('SUMMARY', 'Compatibility Test Event');
        $vevent->add('UID', 'compatibility-test-'.uniqid());

        // Use sabre/vobject's EventIterator to expand the RRULE
        $iterator = new EventIterator($vevent);

        $occurrences = [];
        $count = 0;

        foreach ($iterator as $occurrence) {
            if ($limit !== null && $count >= $limit) {
                break;
            }

            // Get the DTSTART from the iterator directly
            $occurrenceDate = $iterator->getDtStart();

            // Convert DateTime to DateTimeImmutable if needed
            if ($occurrenceDate instanceof \DateTime) {
                $occurrences[] = DateTimeImmutable::createFromMutable($occurrenceDate);
            } else {
                $occurrences[] = $occurrenceDate;
            }

            ++$count;

            // Safety break for infinite sequences
            if ($count > 1000) {
                break;
            }
        }

        return $occurrences;
    }

    /**
     * Compare occurrence arrays and assert they are identical.
     *
     * @param array<DateTimeImmutable> $rrulerOccurrences
     * @param array<DateTimeImmutable> $sabreOccurrences
     * @param string $rruleString The RRULE being tested (for error messages)
     * @param string $testDescription Description of the test case
     */
    protected function assertOccurrencesMatch(
        array $rrulerOccurrences,
        array $sabreOccurrences,
        string $rruleString,
        string $testDescription = '',
    ): void {
        $this->assertCount(
            count($sabreOccurrences),
            $rrulerOccurrences,
            "Occurrence count mismatch for RRULE '{$rruleString}'".
            ($testDescription ? " ({$testDescription})" : '')
        );

        foreach ($sabreOccurrences as $index => $sabreOccurrence) {
            $this->assertTrue(
                isset($rrulerOccurrences[$index]),
                "Missing occurrence at index {$index} for RRULE '{$rruleString}'".
                ($testDescription ? " ({$testDescription})" : '')
            );

            $this->assertEquals(
                $sabreOccurrence,
                $rrulerOccurrences[$index],
                "Occurrence mismatch at index {$index} for RRULE '{$rruleString}'".
                ($testDescription ? " ({$testDescription})" : '').
                ". Expected: {$sabreOccurrence->format('Y-m-d H:i:s')}, ".
                "Got: {$rrulerOccurrences[$index]->format('Y-m-d H:i:s')}"
            );
        }
    }

    /**
     * Test RRULE compatibility between Rruler and sabre/vobject.
     *
     * @param string $rruleString The RRULE string to test
     * @param DateTimeImmutable $start The start date
     * @param int $limit Number of occurrences to compare
     * @param string $testDescription Optional description for the test
     */
    protected function assertRruleCompatibility(
        string $rruleString,
        DateTimeImmutable $start,
        int $limit = 10,
        string $testDescription = '',
    ): void {
        $rrulerOccurrences = $this->getRrulerOccurrences($rruleString, $start, $limit);
        $sabreOccurrences = $this->getSabreOccurrences($rruleString, $start, $limit);

        $this->assertOccurrencesMatch(
            $rrulerOccurrences,
            $sabreOccurrences,
            $rruleString,
            $testDescription
        );
    }

    /**
     * Format occurrences for debugging output.
     *
     * @param array<DateTimeImmutable> $occurrences
     */
    protected function formatOccurrences(array $occurrences): string
    {
        return '['.implode(', ', array_map(
            fn (DateTimeImmutable $dt) => $dt->format('Y-m-d H:i:s'),
            $occurrences
        )).']';
    }
}
