<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Occurrence\OccurrenceValidator;
use EphemeralTodos\Rruler\Rrule;

final class DefaultOccurrenceValidator implements OccurrenceValidator
{
    public function __construct(
        private OccurrenceGenerator $occurrenceGenerator,
    ) {
    }

    public function isValidOccurrence(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $candidate,
    ): bool {
        // Quick check: candidate must be at or after start date
        if ($candidate < $start) {
            return false;
        }

        // Quick check: if UNTIL is set and candidate is after UNTIL, it's invalid
        if ($rrule->hasUntil() && $candidate > $rrule->getUntil()) {
            return false;
        }

        // Use the generator to check if the candidate appears in the sequence
        foreach ($this->occurrenceGenerator->generateOccurrences($rrule, $start) as $occurrence) {
            if ($occurrence == $candidate) {
                return true;
            }

            // If we've passed the candidate date without finding it, it's invalid
            if ($occurrence > $candidate) {
                break;
            }
        }

        return false;
    }
}
