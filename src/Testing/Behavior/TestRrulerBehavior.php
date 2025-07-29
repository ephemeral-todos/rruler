<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Behavior;

use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\Attributes\Before;

/**
 * @phpstan-ignore trait.unused
 */
trait TestRrulerBehavior
{
    protected Rruler $testRruler;

    #[Before]
    public function setUpTestRruler(): void
    {
        $this->testRruler = new Rruler();
    }
}
