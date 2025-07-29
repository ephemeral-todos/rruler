<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use DateTimeImmutable;
use Stringable;

final readonly class Rrule implements Stringable
{
    public function __construct(
        private string $frequency,
        private int $interval,
        private ?int $count,
        private ?DateTimeImmutable $until,
    ) {
    }

    public function getFrequency(): string
    {
        return $this->frequency;
    }

    public function getInterval(): int
    {
        return $this->interval;
    }

    public function getCount(): ?int
    {
        return $this->count;
    }

    public function getUntil(): ?DateTimeImmutable
    {
        return $this->until;
    }

    public function hasCount(): bool
    {
        return $this->count !== null;
    }

    public function hasUntil(): bool
    {
        return $this->until !== null;
    }

    /**
     * @return array{freq: string, interval: int, count: int|null, until: DateTimeImmutable|null}
     */
    public function toArray(): array
    {
        return [
            'freq' => $this->frequency,
            'interval' => $this->interval,
            'count' => $this->count,
            'until' => $this->until,
        ];
    }

    public function __toString(): string
    {
        $parts = [];

        $parts[] = "FREQ={$this->frequency}";

        if ($this->interval !== 1) {
            $parts[] = "INTERVAL={$this->interval}";
        }

        if ($this->count !== null) {
            $parts[] = "COUNT={$this->count}";
        }

        if ($this->until !== null) {
            $utcUntil = $this->until->setTimezone(new \DateTimeZone('UTC'));
            $parts[] = 'UNTIL='.$utcUntil->format('Ymd\THis\Z');
        }

        return implode(';', $parts);
    }
}
