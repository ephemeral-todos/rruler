<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Enhanced DateTime parser with support for various calendar application formats.
 *
 * Uses the standard DateTimeParser with robust handling of format variations
 * found in real-world iCalendar files from Microsoft Outlook, Google Calendar,
 * and Apple Calendar, plus fallback mechanisms for malformed dates.
 */
final class EnhancedDateTimeParser
{
    private readonly DateTimeParser $baseParser;

    public function __construct()
    {
        $this->baseParser = new DateTimeParser();
    }

    /**
     * Parse with enhanced format support and fallback mechanisms.
     *
     * @param string $value The iCalendar date/time value
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If all parsing attempts fail
     */
    public function parse(string $value): \DateTimeImmutable
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: empty string');
        }

        try {
            // First try standard RFC 5545 parsing
            return $this->baseParser->parse($value);
        } catch (\InvalidArgumentException $e) {
            // If standard parsing fails, try enhanced parsing
            return $this->parseWithFallbacks($value, $e);
        }
    }

    /**
     * Parse with timezone using enhanced format support.
     *
     * @param string $value The iCalendar date/time value
     * @param string $timezone The timezone identifier
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If all parsing attempts fail
     */
    public function parseWithTimezone(string $value, string $timezone): \DateTimeImmutable
    {
        try {
            // First try standard RFC 5545 parsing with timezone
            return $this->baseParser->parseWithTimezone($value, $timezone);
        } catch (\InvalidArgumentException $e) {
            // If standard parsing fails, try enhanced parsing then apply timezone
            $dateTime = $this->parseWithFallbacks($value, $e);

            // If the value was already UTC (ends with Z), ignore the timezone parameter
            if (str_ends_with($value, 'Z')) {
                return $dateTime;
            }

            // Apply timezone to the parsed datetime
            return $this->applyTimezoneToDateTime($dateTime, $timezone);
        }
    }

    /**
     * Parse Microsoft Outlook specific format variations.
     *
     * @param string $value The date/time value from Outlook
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If parsing fails
     */
    public function parseOutlookFormat(string $value): \DateTimeImmutable
    {
        // Outlook generally follows RFC 5545, but may have slight variations
        // Remove any BOM or whitespace that might be present
        $cleaned = trim($value, "\xEF\xBB\xBF \t\n\r\0\x0B");

        // Outlook might export with slightly different casing or formatting
        $cleaned = strtoupper($cleaned);

        return $this->parse($cleaned);
    }

    /**
     * Parse Google Calendar specific format variations.
     *
     * @param string $value The date/time value from Google Calendar
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If parsing fails
     */
    public function parseGoogleFormat(string $value): \DateTimeImmutable
    {
        // Google Calendar strictly follows RFC 5545
        // But we may need to handle any encoding issues
        $cleaned = trim($value);

        return $this->parse($cleaned);
    }

    /**
     * Parse Apple Calendar specific format variations.
     *
     * @param string $value The date/time value from Apple Calendar
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If parsing fails
     */
    public function parseAppleFormat(string $value): \DateTimeImmutable
    {
        // Apple Calendar generally follows RFC 5545
        // Handle any potential encoding or format variations
        $cleaned = trim($value);

        return $this->parse($cleaned);
    }

    /**
     * Attempt parsing with various fallback strategies.
     *
     * @param string $value The original value that failed parsing
     * @param \InvalidArgumentException $originalException The original parsing exception
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If all fallbacks fail
     */
    private function parseWithFallbacks(string $value, \InvalidArgumentException $originalException): \DateTimeImmutable
    {
        $fallbackStrategies = [
            // Clean whitespace and control characters
            fn (string $v): \DateTimeImmutable => $this->parseCleanedValue($v),

            // Handle potential BOM or encoding issues
            fn (string $v): \DateTimeImmutable => $this->parseWithEncodingFixes($v),

            // Try parsing with relaxed validation
            fn (string $v): \DateTimeImmutable => $this->parseWithRelaxedValidation($v),

            // Attempt to fix common malformed patterns
            fn (string $v): \DateTimeImmutable => $this->parseWithCommonFixes($v),
        ];

        foreach ($fallbackStrategies as $strategy) {
            try {
                return $strategy($value);
            } catch (\InvalidArgumentException $e) {
                // Continue to next strategy
                continue;
            }
        }

        // If all fallbacks fail, throw the original exception
        throw $originalException;
    }

    /**
     * Parse value after cleaning whitespace and control characters.
     */
    private function parseCleanedValue(string $value): \DateTimeImmutable
    {
        $cleaned = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        $cleaned = trim($cleaned ?? '');

        if ($cleaned === $value) {
            // No change, so this won't help
            throw new \InvalidArgumentException('No cleaning needed');
        }

        return $this->baseParser->parse($cleaned);
    }

    /**
     * Parse value after fixing encoding issues.
     */
    private function parseWithEncodingFixes(string $value): \DateTimeImmutable
    {
        // Remove UTF-8 BOM
        $cleaned = preg_replace('/^\xEF\xBB\xBF/', '', $value);

        // Convert to uppercase for consistency
        $cleaned = strtoupper($cleaned ?? '');

        if ($cleaned === strtoupper($value)) {
            // No change, so this won't help
            throw new \InvalidArgumentException('No encoding fixes needed');
        }

        return $this->baseParser->parse($cleaned);
    }

    /**
     * Parse with relaxed validation for minor format issues.
     */
    private function parseWithRelaxedValidation(string $value): \DateTimeImmutable
    {
        // Try to fix missing leading zeros
        if (preg_match('/^(\d{4})(\d{1,2})(\d{1,2})(?:T(\d{1,2})(\d{1,2})(\d{1,2}))?Z?$/', $value, $matches)) {
            $year = $matches[1];
            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);

            if (isset($matches[4])) {
                $hour = str_pad($matches[4], 2, '0', STR_PAD_LEFT);
                $minute = str_pad($matches[5], 2, '0', STR_PAD_LEFT);
                $second = str_pad($matches[6], 2, '0', STR_PAD_LEFT);
                $fixed = $year.$month.$day.'T'.$hour.$minute.$second;
                if (str_ends_with($value, 'Z')) {
                    $fixed .= 'Z';
                }
            } else {
                $fixed = $year.$month.$day;
            }

            if ($fixed !== $value) {
                return $this->baseParser->parse($fixed);
            }
        }

        throw new \InvalidArgumentException('No relaxed validation fixes applicable');
    }

    /**
     * Parse after applying common fixes for malformed patterns.
     */
    private function parseWithCommonFixes(string $value): \DateTimeImmutable
    {
        $fixes = [
            // Fix separator issues
            '/(\d{4})-(\d{2})-(\d{2})/' => '$1$2$3',
            '/(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2}):(\d{2})/' => '$1$2$3T$4$5$6',

            // Fix double T issue
            '/(\d{8})TT(\d{6})/' => '$1T$2',

            // Fix missing T separator
            '/(\d{8}) (\d{6})/' => '$1T$2',

            // Fix lowercase z
            '/(\d{8}T\d{6})z$/' => '$1Z',
        ];

        foreach ($fixes as $pattern => $replacement) {
            $fixed = preg_replace($pattern, $replacement, $value);
            if ($fixed !== null && $fixed !== $value) {
                try {
                    return $this->baseParser->parse($fixed);
                } catch (\InvalidArgumentException $e) {
                    // Continue to next fix
                    continue;
                }
            }
        }

        throw new \InvalidArgumentException('No common fixes applicable');
    }

    /**
     * Apply timezone to an existing DateTime object.
     */
    private function applyTimezoneToDateTime(\DateTimeImmutable $dateTime, string $timezone): \DateTimeImmutable
    {
        try {
            $tz = new \DateTimeZone($timezone);

            // Create new datetime in the specified timezone
            return new \DateTimeImmutable(
                $dateTime->format('Y-m-d H:i:s'),
                $tz
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid timezone: '.$timezone, 0, $e);
        }
    }
}
