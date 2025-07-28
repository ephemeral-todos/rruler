<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\OccurrenceGenerator;
use EphemeralTodos\Rruler\Rrule;
use Generator;

final class DefaultOccurrenceGenerator implements OccurrenceGenerator
{
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null
    ): Generator {
        $current = $start;
        $count = 0;
        $maxCount = $limit ?? $rrule->getCount();
        
        // Early termination for COUNT=0
        if ($maxCount === 0) {
            return;
        }

        while (true) {
            // Check UNTIL condition
            if ($rrule->hasUntil() && $current > $rrule->getUntil()) {
                break;
            }

            yield $current;
            $count++;

            // Check COUNT condition
            if ($maxCount !== null && $count >= $maxCount) {
                break;
            }

            $current = $this->getNextOccurrence($rrule, $current);
        }
    }

    public function generateOccurrencesInRange(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd
    ): Generator {
        foreach ($this->generateOccurrences($rrule, $start) as $occurrence) {
            if ($occurrence < $rangeStart) {
                continue;
            }
            
            if ($occurrence > $rangeEnd) {
                break;
            }
            
            yield $occurrence;
        }
    }

    private function getNextOccurrence(Rrule $rrule, DateTimeImmutable $current): DateTimeImmutable
    {
        $interval = $rrule->getInterval();
        
        return match ($rrule->getFrequency()) {
            'DAILY' => $current->modify("+{$interval} days"),
            'WEEKLY' => $current->modify("+{$interval} weeks"),
            'MONTHLY' => $current->modify("+{$interval} months"),
            'YEARLY' => $current->modify("+{$interval} years"),
            default => throw new \InvalidArgumentException("Unsupported frequency: {$rrule->getFrequency()}"),
        };
    }
}