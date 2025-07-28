<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\RruleAst;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use EphemeralTodos\Rruler\Parser\RruleParser;
use Stringable;

final readonly class Rrule implements Stringable
{
    private function __construct(
        private string $frequency,
        private int $interval,
        private ?int $count,
        private ?DateTimeImmutable $until,
    ) {
    }

    public static function fromString(string $rruleString): self
    {
        $parser = new RruleParser();
        $ast = $parser->parse($rruleString);

        return self::fromAst($ast);
    }

    public static function fromAst(RruleAst $ast): self
    {
        /** @var FrequencyNode $frequencyNode */
        $frequencyNode = $ast->getNode('FREQ');
        $frequency = $frequencyNode->getValue();

        $interval = 1; // Default value
        if ($ast->hasNode('INTERVAL')) {
            /** @var IntervalNode $intervalNode */
            $intervalNode = $ast->getNode('INTERVAL');
            $interval = $intervalNode->getValue();
        }

        $count = null;
        if ($ast->hasNode('COUNT')) {
            /** @var CountNode $countNode */
            $countNode = $ast->getNode('COUNT');
            $count = $countNode->getValue();
        }

        $until = null;
        if ($ast->hasNode('UNTIL')) {
            /** @var UntilNode $untilNode */
            $untilNode = $ast->getNode('UNTIL');
            $until = $untilNode->getValue();
        }

        return new self($frequency, $interval, $count, $until);
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
