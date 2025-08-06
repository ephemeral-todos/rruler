<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Testing\Behavior;

use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceValidator;
use PHPUnit\Framework\Attributes\Before;

/**
 * @phpstan-ignore trait.unused
 */
trait TestOccurrenceGenerationBehavior
{
    protected DefaultOccurrenceGenerator $testOccurrenceGenerator;
    protected DefaultOccurrenceValidator $testOccurrenceValidator;

    #[Before]
    public function setUpTestOccurrenceGeneration(): void
    {
        $this->testOccurrenceGenerator = new DefaultOccurrenceGenerator();
        $this->testOccurrenceValidator = new DefaultOccurrenceValidator($this->testOccurrenceGenerator);
    }
}
