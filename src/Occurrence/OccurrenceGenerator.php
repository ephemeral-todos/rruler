<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Rrule;
use Generator;

interface OccurrenceGenerator
{
    /**
     * Generate occurrences from an RRULE with optional limit.
     *
     * @return Generator<DateTimeImmutable>
     */
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null,
    ): Generator;

    /**
     * Generate occurrences within a specific date range.
     *
     * @return Generator<DateTimeImmutable>
     */
    public function generateOccurrencesInRange(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd,
    ): Generator;
}
