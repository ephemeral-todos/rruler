<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Parser;

use EphemeralTodos\Rruler\Exception\ParseException;

final class Tokenizer
{
    /**
     * @return array<string, string>
     */
    public function tokenize(string $rruleString): array
    {
        if (trim($rruleString) === '') {
            throw new ParseException('RRULE string cannot be empty');
        }

        $tokens = [];
        $normalizedString = $this->normalizeWhitespace($rruleString);
        $parameters = explode(';', $normalizedString);

        foreach ($parameters as $parameter) {
            $parameter = trim($parameter);

            if ($parameter === '') {
                continue;
            }

            $this->validateParameterFormat($parameter);

            [$name, $value] = $this->parseParameter($parameter);

            $normalizedName = strtoupper($name);

            if (isset($tokens[$normalizedName])) {
                throw new ParseException("Duplicate parameter: $normalizedName");
            }

            $tokens[$normalizedName] = $value;
        }

        return $tokens;
    }

    private function normalizeWhitespace(string $input): string
    {
        // Replace all whitespace characters (spaces, tabs, newlines) with single spaces
        $normalized = preg_replace('/\s+/', ' ', $input) ?? '';

        // Remove spaces around semicolons and equals signs for consistent parsing
        $normalized = preg_replace('/\s*;\s*/', ';', $normalized) ?? '';
        $normalized = preg_replace('/\s*=\s*/', '=', $normalized) ?? '';

        return trim($normalized);
    }

    private function validateParameterFormat(string $parameter): void
    {
        $equalsCount = substr_count($parameter, '=');

        if ($equalsCount === 0) {
            throw new ParseException("Invalid parameter format: $parameter. Expected parameter=value");
        }

        if ($equalsCount > 1) {
            throw new ParseException("Invalid parameter format: $parameter. Expected parameter=value");
        }

        if (str_starts_with($parameter, '=')) {
            throw new ParseException("Invalid parameter format: $parameter. Expected parameter=value");
        }

        if (str_ends_with($parameter, '=')) {
            throw new ParseException("Invalid parameter format: $parameter. Expected parameter=value");
        }
    }

    /**
     * @return array{string, string}
     */
    private function parseParameter(string $parameter): array
    {
        $parts = explode('=', $parameter, 2);

        return [
            trim($parts[0]),
            trim($parts[1]),
        ];
    }
}
