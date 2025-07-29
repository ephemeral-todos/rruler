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
    public const string NAME = 'FREQ';

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

    private readonly string $value;

    public function __construct(private readonly string $rawValue)
    {
        $this->value = $rawValue;

        if ($this->value === '') {
            throw new CannotBeEmptyException($this);
        }

        if (!in_array($this->value, self::VALID_FREQUENCIES, true)) {
            throw new InvalidChoiceException($this);
        }
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    public static function getChoices(): array
    {
        return self::VALID_FREQUENCIES;
    }
}
