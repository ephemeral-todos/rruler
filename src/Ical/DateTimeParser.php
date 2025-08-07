<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Ical;

/**
 * Parses iCalendar date and date-time values according to RFC 5545.
 *
 * Handles both DATE (YYYYMMDD) and DATE-TIME (YYYYMMDDTHHMMSSZ or YYYYMMDDTHHMMSS)
 * formats as specified in RFC 5545.
 */
final class DateTimeParser
{
    /**
     * Parses an iCalendar date or date-time string.
     *
     * @param string $value The iCalendar date/time value
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If the format is invalid
     */
    public function parse(string $value): \DateTimeImmutable
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: empty string');
        }

        // Check for UTC timestamp (ends with Z)
        if (str_ends_with($value, 'Z')) {
            return $this->parseUtcTimestamp(substr($value, 0, -1));
        }

        // Check for local timestamp (contains T)
        if (str_contains($value, 'T')) {
            return $this->parseLocalTimestamp($value);
        }

        // Must be a date-only value
        return $this->parseDate($value);
    }

    /**
     * Parses an iCalendar date/time string with a specific timezone.
     *
     * @param string $value The iCalendar date/time value
     * @param string $timezone The timezone identifier
     * @return \DateTimeImmutable The parsed date/time
     *
     * @throws \InvalidArgumentException If the format is invalid
     */
    public function parseWithTimezone(string $value, string $timezone): \DateTimeImmutable
    {
        // If the value is already UTC (ends with Z), ignore the timezone parameter
        if (str_ends_with($value, 'Z')) {
            return $this->parse($value);
        }

        // Parse the components but create DateTime in the specified timezone
        if (str_contains($value, 'T')) {
            return $this->parseLocalTimestampInTimezone($value, $timezone);
        } else {
            return $this->parseDateInTimezone($value, $timezone);
        }
    }

    /**
     * Parses a UTC timestamp (without the Z suffix).
     *
     * @param string $value The timestamp value (YYYYMMDDTHHMMSS)
     * @return \DateTimeImmutable The parsed date/time in UTC
     */
    private function parseUtcTimestamp(string $value): \DateTimeImmutable
    {
        if (!preg_match('/^(\d{8})T(\d{6})$/', $value, $matches)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value.'Z');
        }

        $datePart = $matches[1];
        $timePart = $matches[2];

        $year = substr($datePart, 0, 4);
        $month = substr($datePart, 4, 2);
        $day = substr($datePart, 6, 2);

        $hour = substr($timePart, 0, 2);
        $minute = substr($timePart, 2, 2);
        $second = substr($timePart, 4, 2);

        $this->validateDateTimeParts($year, $month, $day, $hour, $minute, $second);

        try {
            return new \DateTimeImmutable(
                sprintf('%s-%s-%s %s:%s:%s UTC', $year, $month, $day, $hour, $minute, $second)
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value.'Z', 0, $e);
        }
    }

    /**
     * Parses a local timestamp.
     *
     * @param string $value The timestamp value (YYYYMMDDTHHMMSS)
     * @return \DateTimeImmutable The parsed date/time
     */
    private function parseLocalTimestamp(string $value): \DateTimeImmutable
    {
        if (!preg_match('/^(\d{8})T(\d{6})$/', $value, $matches)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value);
        }

        $datePart = $matches[1];
        $timePart = $matches[2];

        $year = substr($datePart, 0, 4);
        $month = substr($datePart, 4, 2);
        $day = substr($datePart, 6, 2);

        $hour = substr($timePart, 0, 2);
        $minute = substr($timePart, 2, 2);
        $second = substr($timePart, 4, 2);

        $this->validateDateTimeParts($year, $month, $day, $hour, $minute, $second);

        try {
            return new \DateTimeImmutable(
                sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $minute, $second)
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value, 0, $e);
        }
    }

    /**
     * Parses a date-only value.
     *
     * @param string $value The date value (YYYYMMDD)
     * @return \DateTimeImmutable The parsed date at midnight
     */
    private function parseDate(string $value): \DateTimeImmutable
    {
        if (!preg_match('/^(\d{8})$/', $value)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value);
        }

        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);

        $this->validateDateTimeParts($year, $month, $day);

        try {
            return new \DateTimeImmutable(sprintf('%s-%s-%s 00:00:00', $year, $month, $day));
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value, 0, $e);
        }
    }

    /**
     * Parses a local timestamp in a specific timezone.
     *
     * @param string $value The timestamp value (YYYYMMDDTHHMMSS)
     * @param string $timezone The timezone identifier
     * @return \DateTimeImmutable The parsed date/time
     */
    private function parseLocalTimestampInTimezone(string $value, string $timezone): \DateTimeImmutable
    {
        if (!preg_match('/^(\d{8})T(\d{6})$/', $value, $matches)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value);
        }

        $datePart = $matches[1];
        $timePart = $matches[2];

        $year = substr($datePart, 0, 4);
        $month = substr($datePart, 4, 2);
        $day = substr($datePart, 6, 2);

        $hour = substr($timePart, 0, 2);
        $minute = substr($timePart, 2, 2);
        $second = substr($timePart, 4, 2);

        $this->validateDateTimeParts($year, $month, $day, $hour, $minute, $second);

        try {
            $tz = new \DateTimeZone($timezone);

            return new \DateTimeImmutable(
                sprintf('%s-%s-%s %s:%s:%s', $year, $month, $day, $hour, $minute, $second),
                $tz
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value, 0, $e);
        }
    }

    /**
     * Parses a date in a specific timezone.
     *
     * @param string $value The date value (YYYYMMDD)
     * @param string $timezone The timezone identifier
     * @return \DateTimeImmutable The parsed date at midnight
     */
    private function parseDateInTimezone(string $value, string $timezone): \DateTimeImmutable
    {
        if (!preg_match('/^(\d{8})$/', $value)) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value);
        }

        $year = substr($value, 0, 4);
        $month = substr($value, 4, 2);
        $day = substr($value, 6, 2);

        $this->validateDateTimeParts($year, $month, $day);

        try {
            $tz = new \DateTimeZone($timezone);

            return new \DateTimeImmutable(
                sprintf('%s-%s-%s 00:00:00', $year, $month, $day),
                $tz
            );
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format: '.$value, 0, $e);
        }
    }

    /**
     * Validates date and time parts.
     */
    private function validateDateTimeParts(
        string $year,
        string $month,
        string $day,
        ?string $hour = null,
        ?string $minute = null,
        ?string $second = null,
    ): void {
        $yearInt = (int) $year;
        $monthInt = (int) $month;
        $dayInt = (int) $day;

        if ($yearInt < 1 || $yearInt > 9999) {
            throw new \InvalidArgumentException('Invalid year: '.$year);
        }

        if ($monthInt < 1 || $monthInt > 12) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format');
        }

        if ($dayInt < 1 || $dayInt > 31) {
            throw new \InvalidArgumentException('Invalid iCalendar date/time format');
        }

        // Validate actual date (handles leap years and month lengths)
        if (!checkdate($monthInt, $dayInt, $yearInt)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid date: %s-%s-%s', $year, $month, $day)
            );
        }

        if ($hour !== null) {
            $hourInt = (int) $hour;
            if ($hourInt < 0 || $hourInt > 23) {
                throw new \InvalidArgumentException('Invalid iCalendar date/time format');
            }
        }

        if ($minute !== null) {
            $minuteInt = (int) $minute;
            if ($minuteInt < 0 || $minuteInt > 59) {
                throw new \InvalidArgumentException('Invalid iCalendar date/time format');
            }
        }

        if ($second !== null) {
            $secondInt = (int) $second;
            if ($secondInt < 0 || $secondInt > 59) {
                throw new \InvalidArgumentException('Invalid iCalendar date/time format');
            }
        }
    }
}
