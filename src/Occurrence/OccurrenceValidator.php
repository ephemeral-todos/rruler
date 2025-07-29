<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Rrule;

interface OccurrenceValidator
{
    /**
     * Check if a specific DateTime represents a valid occurrence.
     */
    public function isValidOccurrence(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $candidate,
    ): bool;
}
