<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler;

use EphemeralTodos\Rruler\Parser\Ast\ByDayNode;
use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use EphemeralTodos\Rruler\Parser\RruleParser;

final class Rruler
{
    public function __construct(private RruleParser $parser = new RruleParser())
    {
    }

    public function parse(string $rruleString): Rrule
    {
        $ast = $this->parser->parse($rruleString);

        $frequencyNode = $ast->getNode(FrequencyNode::class);
        $frequency = $frequencyNode->getValue();

        $interval = 1; // Default value
        if ($ast->hasNode(IntervalNode::class)) {
            $intervalNode = $ast->getNode(IntervalNode::class);
            $interval = $intervalNode->getValue();
        }

        $count = null;
        if ($ast->hasNode(CountNode::class)) {
            $countNode = $ast->getNode(CountNode::class);
            $count = $countNode->getValue();
        }

        $until = null;
        if ($ast->hasNode(UntilNode::class)) {
            $untilNode = $ast->getNode(UntilNode::class);
            $until = $untilNode->getValue();
        }

        $byDay = null;
        if ($ast->hasNode(ByDayNode::class)) {
            $byDayNode = $ast->getNode(ByDayNode::class);
            $byDay = $byDayNode->getValue();
        }

        return new Rrule($frequency, $interval, $count, $until, $byDay);
    }
}
