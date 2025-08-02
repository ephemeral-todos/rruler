<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

final class NodeTypeUtils
{
    private const array PRETTY_NAMES_MAP = [
        FrequencyNode::class => 'Frequency',
        IntervalNode::class => 'Interval',
        CountNode::class => 'Count',
        UntilNode::class => 'Until',
        ByDayNode::class => 'BYDAY',
    ];

    public static function toPrettyName(string|object $nodeClass): string
    {
        if (!is_string($nodeClass)) {
            $nodeClass = get_class($nodeClass);
        }

        if (!array_key_exists($nodeClass, self::PRETTY_NAMES_MAP)) {
            return 'Unknown';
        }

        return self::PRETTY_NAMES_MAP[$nodeClass];
    }
}
