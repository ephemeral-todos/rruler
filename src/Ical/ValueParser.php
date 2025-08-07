<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Handles parsing and escaping of iCalendar values according to RFC 5545.
 *
 * This class provides methods to escape and unescape special characters
 * in iCalendar property values, and to parse comma-separated list values
 * while respecting escaping rules.
 */
final class ValueParser
{
    /**
     * Unescapes an iCalendar value according to RFC 5545 rules.
     *
     * @param string $value The escaped value
     * @return string The unescaped value
     */
    public function unescape(string $value): string
    {
        // RFC 5545 escape sequences:
        // \\ -> \
        // \, -> ,
        // \; -> ;
        // \n or \N -> newline
        // \t or \T -> tab

        $replacements = [
            '\\\\' => '\\',      // Must be first to avoid double replacement
            '\\,' => ',',
            '\\;' => ';',
            '\\n' => "\n",
            '\\N' => "\n",
            '\\t' => "\t",
            '\\T' => "\t",
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $value);
    }

    /**
     * Escapes an iCalendar value according to RFC 5545 rules.
     *
     * @param string $value The value to escape
     * @return string The escaped value
     */
    public function escape(string $value): string
    {
        // For proper escaping, we need to handle all backslashes first,
        // then handle the special characters

        // 1. Escape all backslashes (this will double any existing backslashes)
        $result = str_replace('\\', '\\\\', $value);

        // 2. Handle actual newlines and tabs (convert to escape sequences)
        $result = str_replace("\n", '\\n', $result);
        $result = str_replace("\t", '\\t', $result);

        // 3. Handle commas and semicolons
        $result = str_replace(',', '\\,', $result);
        $result = str_replace(';', '\\;', $result);

        return $result;
    }

    /**
     * Parses a comma-separated list value, respecting escape sequences.
     *
     * @param string $value The comma-separated value
     * @return array<string> Array of individual values
     */
    public function parseList(string $value): array
    {
        if (empty($value)) {
            return [''];
        }

        $items = [];
        $current = '';
        $length = strlen($value);
        $i = 0;

        while ($i < $length) {
            $char = $value[$i];

            if ($char === '\\' && $i + 1 < $length) {
                // Handle escape sequence
                $nextChar = $value[$i + 1];

                switch ($nextChar) {
                    case '\\':
                        $current .= '\\';
                        break;
                    case ',':
                        $current .= ',';
                        break;
                    case ';':
                        $current .= ';';
                        break;
                    case 'n':
                    case 'N':
                        $current .= "\n";
                        break;
                    case 't':
                    case 'T':
                        $current .= "\t";
                        break;
                    default:
                        // Unknown escape sequence - preserve as is
                        $current .= $char.$nextChar;
                        break;
                }

                $i += 2; // Skip both characters
            } elseif ($char === ',') {
                // Found unescaped comma - end of current item
                $items[] = trim($current);
                $current = '';
                ++$i;
            } else {
                // Regular character
                $current .= $char;
                ++$i;
            }
        }

        // Add the last item
        $items[] = trim($current);

        return $items;
    }
}
