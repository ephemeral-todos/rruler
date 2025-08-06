<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use DateTimeImmutable;
use Stringable;

final readonly class Rrule implements Stringable
{
    /**
     * @param array<array{position: int|null, weekday: string}>|null $byDay
     * @param array<int>|null $byMonthDay
     * @param array<int>|null $byMonth
     * @param array<int>|null $byWeekNo
     * @param array<int>|null $bySetPos
     */
    public function __construct(
        private string $frequency,
        private int $interval,
        private ?int $count,
        private ?DateTimeImmutable $until,
        private ?array $byDay = null,
        private ?array $byMonthDay = null,
        private ?array $byMonth = null,
        private ?array $byWeekNo = null,
        private ?array $bySetPos = null,
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
     * @return array<array{position: int|null, weekday: string}>|null
     */
    public function getByDay(): ?array
    {
        return $this->byDay;
    }

    public function hasByDay(): bool
    {
        return $this->byDay !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByMonthDay(): ?array
    {
        return $this->byMonthDay;
    }

    public function hasByMonthDay(): bool
    {
        return $this->byMonthDay !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByMonth(): ?array
    {
        return $this->byMonth;
    }

    public function hasByMonth(): bool
    {
        return $this->byMonth !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getByWeekNo(): ?array
    {
        return $this->byWeekNo;
    }

    public function hasByWeekNo(): bool
    {
        return $this->byWeekNo !== null;
    }

    /**
     * @return array<int>|null
     */
    public function getBySetPos(): ?array
    {
        return $this->bySetPos;
    }

    public function hasBySetPos(): bool
    {
        return $this->bySetPos !== null;
    }

    /**
     * @return array{freq: string, interval: int, count: int|null, until: DateTimeImmutable|null, byDay: array<array{position: int|null, weekday: string}>|null, byMonthDay: array<int>|null, byMonth: array<int>|null, byWeekNo: array<int>|null, bySetPos: array<int>|null}
     */
    public function toArray(): array
    {
        return [
            'freq' => $this->frequency,
            'interval' => $this->interval,
            'count' => $this->count,
            'until' => $this->until,
            'byDay' => $this->byDay,
            'byMonthDay' => $this->byMonthDay,
            'byMonth' => $this->byMonth,
            'byWeekNo' => $this->byWeekNo,
            'bySetPos' => $this->bySetPos,
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

        if ($this->byDay !== null) {
            $byDayStrings = [];
            foreach ($this->byDay as $daySpec) {
                $byDayStrings[] = ($daySpec['position'] !== null ? $daySpec['position'] : '').$daySpec['weekday'];
            }
            $parts[] = 'BYDAY='.implode(',', $byDayStrings);
        }

        if ($this->byMonthDay !== null) {
            $parts[] = 'BYMONTHDAY='.implode(',', $this->byMonthDay);
        }

        if ($this->byMonth !== null) {
            $parts[] = 'BYMONTH='.implode(',', $this->byMonth);
        }

        if ($this->byWeekNo !== null) {
            $parts[] = 'BYWEEKNO='.implode(',', $this->byWeekNo);
        }

        if ($this->bySetPos !== null) {
            $parts[] = 'BYSETPOS='.implode(',', $this->bySetPos);
        }

        return implode(';', $parts);
    }
}
