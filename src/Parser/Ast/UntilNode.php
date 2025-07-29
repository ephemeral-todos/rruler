<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser\Ast;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\CannotBeEmptyException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use Exception;

final class UntilNode implements Node
{
    public const string NAME = 'UNTIL';

    private readonly DateTimeImmutable $value;

    public function __construct(private readonly string $rawValue)
    {
        if ($rawValue === '') {
            throw new CannotBeEmptyException($this);
        }

        $this->value = $this->parseUntilDate($rawValue);
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getValue(): DateTimeImmutable
    {
        return $this->value;
    }

    public function getRawValue(): string
    {
        return $this->rawValue;
    }

    private function parseUntilDate(string $dateString): DateTimeImmutable
    {
        // RFC 5545 format: YYYYMMDDTHHMMSSZ
        if (!preg_match('/^\d{8}T\d{6}Z$/', $dateString)) {
            throw new ValidationException($this, sprintf('Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: %s', $dateString));
        }

        $year = substr($dateString, 0, 4);
        $month = substr($dateString, 4, 2);
        $day = substr($dateString, 6, 2);
        $hour = substr($dateString, 9, 2);
        $minute = substr($dateString, 11, 2);
        $second = substr($dateString, 13, 2);

        $formatted = sprintf('%s-%s-%sT%s:%s:%sZ', $year, $month, $day, $hour, $minute, $second);

        try {
            return new DateTimeImmutable($formatted);
        } catch (Exception) {
            throw new ValidationException($this, sprintf('Invalid until date format. Expected YYYYMMDDTHHMMSSZ, got: %s', $dateString));
        }
    }
}
