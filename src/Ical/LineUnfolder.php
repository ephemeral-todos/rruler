<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Handles RFC 5545 line unfolding for iCalendar content.
 *
 * According to RFC 5545, content lines are limited to 75 octets excluding
 * the line break. Longer lines are "folded" by inserting a CRLF sequence
 * followed by a white space character (space or tab).
 *
 * This class unfolds these lines back to their original format.
 */
final class LineUnfolder
{
    /**
     * Unfolds a single iCalendar content string according to RFC 5545 rules.
     *
     * @param string $input The folded iCalendar content
     * @return string The unfolded content
     */
    public function unfold(string $input): string
    {
        if (empty($input)) {
            return '';
        }

        // Replace CRLF followed by space or tab with a single space
        // This restores the original space that was broken by folding
        return preg_replace('/\r\n[ \t]/', ' ', $input) ?? $input;
    }

    /**
     * Unfolds iCalendar content and splits it into individual lines.
     *
     * @param string $input The folded iCalendar content
     * @return array<string> Array of unfolded lines
     */
    public function unfoldToLines(string $input): array
    {
        if (empty($input)) {
            return [];
        }

        // First unfold all the folded lines
        $unfolded = $this->unfold($input);

        // Split into individual lines, handling both CRLF and LF line endings
        $lines = preg_split('/\r\n|\n|\r/', $unfolded);
        if ($lines === false) {
            $lines = [];
        }

        // Filter out empty lines that might result from splitting
        return array_values(array_filter($lines, fn ($line) => $line !== ''));
    }
}
