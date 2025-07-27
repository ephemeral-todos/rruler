<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\InvalidChoiceException;

/**
 * @phpstan-type FrequencyString self::FREQUENCY_*
 */
final class FrequencyNode implements Node, NodeWithChoices
{
    public const string FREQUENCY_DAILY = 'DAILY';
    public const string FREQUENCY_WEEKLY = 'WEEKLY';
    public const string FREQUENCY_MONTHLY = 'MONTHLY';
    public const string FREQUENCY_YEARLY = 'YEARLY';

    private const VALID_FREQUENCIES = [
        self::FREQUENCY_DAILY,
        self::FREQUENCY_WEEKLY,
        self::FREQUENCY_MONTHLY,
        self::FREQUENCY_YEARLY,
    ];

    private readonly string $frequency;

    public function __construct(private readonly string $rawFrequency)
    {
        $this->frequency = strtoupper(trim($rawFrequency));

        if ($this->frequency === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!in_array($this->frequency, self::VALID_FREQUENCIES, true)) {
            throw new InvalidChoiceException($this);
        }
    }

    public function getValue(): string
    {
        return $this->frequency;
    }

    public function getRawValue(): string
    {
        return $this->rawFrequency;
    }

    public static function getChoices(): array
    {
        return self::VALID_FREQUENCIES;
    }
}
