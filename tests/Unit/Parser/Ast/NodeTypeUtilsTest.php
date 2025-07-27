<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Parser\Ast;

use EphemeralTodos\Rruler\Parser\Ast\CountNode;
use EphemeralTodos\Rruler\Parser\Ast\FrequencyNode;
use EphemeralTodos\Rruler\Parser\Ast\IntervalNode;
use EphemeralTodos\Rruler\Parser\Ast\NodeTypeUtils;
use EphemeralTodos\Rruler\Parser\Ast\UntilNode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NodeTypeUtilsTest extends TestCase
{
    #[DataProvider('providePrettyNameData')]
    public function testToPrettyName(string $expected, string|object $input): void
    {
        $this->assertEquals($expected, NodeTypeUtils::toPrettyName($input));
    }

    public static function providePrettyNameData(): array
    {
        return [
            ['Frequency', FrequencyNode::class],
            ['Interval', IntervalNode::class],
            ['Count', CountNode::class],
            ['Until', UntilNode::class],
            ['Frequency', new FrequencyNode('DAILY')],
            ['Interval', new IntervalNode('5')],
            ['Count', new CountNode('10')],
            ['Until', new UntilNode('20241231T235959Z')],
            ['Unknown', 'SomeRandomClass'],
        ];
    }
}
