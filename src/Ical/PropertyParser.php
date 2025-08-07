<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Parses iCalendar property lines according to RFC 5545.
 *
 * Handles parsing of property lines in the format:
 * - name:value
 * - name;param=value;param=value:value
 *
 * Property names are normalized to uppercase, while values and
 * parameter values preserve their original case.
 */
final class PropertyParser
{
    /**
     * Parses an iCalendar property line into a Property object.
     *
     * @param string $propertyLine The property line to parse
     * @return Property The parsed property
     *
     * @throws \InvalidArgumentException If the property line is empty or invalid
     */
    public function parse(string $propertyLine): Property
    {
        if (empty($propertyLine)) {
            throw new \InvalidArgumentException('Empty property line provided');
        }

        // Find the first colon to separate property name/parameters from value
        $colonPos = strpos($propertyLine, ':');
        if ($colonPos === false) {
            throw new \InvalidArgumentException('Invalid property format');
        }

        $nameAndParams = substr($propertyLine, 0, $colonPos);
        $value = substr($propertyLine, $colonPos + 1);

        // Parse the property name and parameters
        $semicolonPos = strpos($nameAndParams, ';');
        if ($semicolonPos === false) {
            // No parameters, just the property name
            $propertyName = strtoupper(trim($nameAndParams));
            $parameters = [];
        } else {
            // Property has parameters
            $propertyName = strtoupper(trim(substr($nameAndParams, 0, $semicolonPos)));
            $paramString = substr($nameAndParams, $semicolonPos + 1);
            $parameters = $this->parseParameters($paramString);
        }

        return new Property($propertyName, $value, $parameters);
    }

    /**
     * Parses parameter string into an associative array.
     *
     * @param string $paramString The parameter string to parse
     * @return array<string, string> The parsed parameters
     */
    private function parseParameters(string $paramString): array
    {
        $parameters = [];

        // Split parameters by semicolon, but be careful with quoted values
        $parts = $this->splitParameters($paramString);

        foreach ($parts as $part) {
            $equalPos = strpos($part, '=');
            if ($equalPos === false) {
                continue; // Skip malformed parameters
            }

            $paramName = strtoupper(trim(substr($part, 0, $equalPos)));
            $paramValue = trim(substr($part, $equalPos + 1));

            // Remove quotes from parameter values if present
            if (strlen($paramValue) >= 2 && $paramValue[0] === '"' && $paramValue[-1] === '"') {
                $paramValue = substr($paramValue, 1, -1);
            }

            $parameters[$paramName] = $paramValue;
        }

        return $parameters;
    }

    /**
     * Splits parameter string by semicolons, respecting quoted values.
     *
     * @param string $paramString The parameter string to split
     * @return array<string> The split parameter parts
     */
    private function splitParameters(string $paramString): array
    {
        $parts = [];
        $current = '';
        $inQuotes = false;
        $length = strlen($paramString);

        for ($i = 0; $i < $length; ++$i) {
            $char = $paramString[$i];

            if ($char === '"' && ($i === 0 || $paramString[$i - 1] !== '\\')) {
                $inQuotes = !$inQuotes;
                $current .= $char;
            } elseif ($char === ';' && !$inQuotes) {
                if (!empty(trim($current))) {
                    $parts[] = trim($current);
                }
                $current = '';
            } else {
                $current .= $char;
            }
        }

        // Add the last part
        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return $parts;
    }
}
