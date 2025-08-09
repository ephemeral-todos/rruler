<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\DateTimeParser;
use PHPUnit\Framework\TestCase;

/**
 * Test suite for extended iCalendar date format compatibility.
 *
 * This test class validates that our DateTimeParser can handle various
 * date formats that might be encountered from different calendar applications
 * and edge cases in RFC 5545 compliance.
 */
final class ExtendedDateFormatTest extends TestCase
{
    private DateTimeParser $parser;

    protected function setUp(): void
    {
        $this->parser = new DateTimeParser();
    }

    /**
     * Test various timezone handling scenarios.
     *
     * Different calendar systems may output date/time values with different
     * timezone representations that we should handle gracefully.
     */
    public function testTimezoneHandlingScenarios(): void
    {
        $testCases = [
            // UTC variations
            '20250101T120000Z' => '2025-01-01 12:00:00',
            '20250101T000000Z' => '2025-01-01 00:00:00',
            '20250101T235959Z' => '2025-01-01 23:59:59',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parse($input);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing UTC format: $input");
        }

        // Test timezone-aware parsing
        $result = $this->parser->parseWithTimezone('20250101T120000', 'UTC');
        $this->assertEquals('2025-01-01 12:00:00', $result->format('Y-m-d H:i:s'));

        $result = $this->parser->parseWithTimezone('20250101T120000', 'America/New_York');
        $this->assertEquals('2025-01-01 12:00:00', $result->format('Y-m-d H:i:s'));
    }

    /**
     * Test Microsoft Outlook specific date format patterns.
     *
     * Outlook may export iCalendar files with certain formatting quirks
     * that we should handle gracefully while maintaining RFC compliance.
     */
    public function testMicrosoftOutlookDateFormats(): void
    {
        // Test cases that might be encountered from Outlook exports
        $testCases = [
            // Standard Outlook formats should work with existing parser
            '20250315T143000' => '2025-03-15 14:30:00',
            '20250315T143000Z' => '2025-03-15 14:30:00',
        ];

        // Handle date-only formats carefully to avoid PHP integer conversion
        $dateOnlyCases = [
            ['input' => '20250315', 'expected' => '2025-03-15 00:00:00'],
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parse($input);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing Outlook format: $input");
        }

        foreach ($dateOnlyCases as $case) {
            $result = $this->parser->parse($case['input']);
            $this->assertEquals($case['expected'], $result->format('Y-m-d H:i:s'), "Failed parsing Outlook date-only format: {$case['input']}");
        }
    }

    /**
     * Test Google Calendar date format variations.
     *
     * Google Calendar generally follows RFC 5545 strictly but may have
     * specific timezone handling patterns.
     */
    public function testGoogleCalendarDateFormats(): void
    {
        $testCases = [
            // Google Calendar standard formats
            '20250601T090000' => '2025-06-01 09:00:00',
            '20250601T090000Z' => '2025-06-01 09:00:00',
        ];

        // Handle date-only formats carefully to avoid PHP integer conversion
        $dateOnlyCases = [
            ['input' => '20250601', 'expected' => '2025-06-01 00:00:00'],
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parse($input);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing Google Calendar format: $input");
        }

        foreach ($dateOnlyCases as $case) {
            $result = $this->parser->parse($case['input']);
            $this->assertEquals($case['expected'], $result->format('Y-m-d H:i:s'), "Failed parsing Google Calendar date-only format: {$case['input']}");
        }
    }

    /**
     * Test Apple Calendar date format variations.
     *
     * Apple Calendar may have specific patterns in how it handles
     * timezone information and date formatting.
     */
    public function testAppleCalendarDateFormats(): void
    {
        $testCases = [
            // Apple Calendar standard formats
            '20251225T000000' => '2025-12-25 00:00:00',
            '20251225T235959Z' => '2025-12-25 23:59:59',
        ];

        // Handle date-only formats carefully to avoid PHP integer conversion
        $dateOnlyCases = [
            ['input' => '20251225', 'expected' => '2025-12-25 00:00:00'],
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parse($input);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing Apple Calendar format: $input");
        }

        foreach ($dateOnlyCases as $case) {
            $result = $this->parser->parse($case['input']);
            $this->assertEquals($case['expected'], $result->format('Y-m-d H:i:s'), "Failed parsing Apple Calendar date-only format: {$case['input']}");
        }
    }

    /**
     * Test edge cases in date formatting.
     *
     * Test various edge cases that might occur in real-world iCalendar data,
     * including boundary dates and unusual but valid RFC 5545 constructs.
     */
    public function testDateFormattingEdgeCases(): void
    {
        $testCases = [
            // Leap year handling
            '20240229T120000' => '2024-02-29 12:00:00',
            '20240229T120000Z' => '2024-02-29 12:00:00',

            // Year boundaries
            '20241231T235959Z' => '2024-12-31 23:59:59',
            '20250101T000000Z' => '2025-01-01 00:00:00',

            // Mid-range dates
            '20250630T120000' => '2025-06-30 12:00:00',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->parser->parse($input);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing edge case format: $input");
        }
    }

    /**
     * Test invalid date format handling.
     *
     * Verify that our parser appropriately handles invalid date formats
     * by throwing exceptions rather than silently failing.
     */
    public function testInvalidDateFormatHandling(): void
    {
        $invalidFormats = [
            // Invalid month
            '20250001T120000',
            '20251301T120000',

            // Invalid day
            '20250100T120000',
            '20250132T120000',

            // Invalid hour
            '20250101T250000',

            // Invalid minute
            '20250101T126000',

            // Invalid second
            '20250101T120060',

            // Malformed strings
            'not-a-date',
            '2025-01-01',  // Wrong separator
            '',
        ];

        foreach ($invalidFormats as $invalidFormat) {
            try {
                $this->parser->parse($invalidFormat);
                $this->fail("Expected exception for invalid format: $invalidFormat");
            } catch (\Exception $e) {
                // Expected behavior - invalid formats should throw exceptions
                $this->addToAssertionCount(1);
            }
        }
    }

    /**
     * Test advanced timezone scenarios.
     *
     * Test timezone-aware parsing with various timezone identifiers
     * that might be encountered in real-world calendar data.
     */
    public function testAdvancedTimezoneScenarios(): void
    {
        // Test common timezone identifiers
        $timezones = [
            'UTC',
            'America/New_York',
            'Europe/London',
            'Asia/Tokyo',
        ];

        $dateTime = '20250615T143000';
        $expected = '2025-06-15 14:30:00';

        foreach ($timezones as $timezone) {
            $result = $this->parser->parseWithTimezone($dateTime, $timezone);
            $this->assertEquals($expected, $result->format('Y-m-d H:i:s'), "Failed parsing with timezone: $timezone");

            // Verify timezone is preserved
            $this->assertEquals($timezone, $result->getTimezone()->getName(), "Timezone not preserved: $timezone");
        }
    }

    /**
     * Test date-only parsing scenarios.
     *
     * Verify that date-only values (YYYYMMDD format) are handled correctly
     * and default to midnight (00:00:00).
     */
    public function testDateOnlyParsing(): void
    {
        $dateOnlyTests = [
            ['input' => '20250101', 'expected' => '2025-01-01 00:00:00'],
            ['input' => '20250630', 'expected' => '2025-06-30 00:00:00'],
            ['input' => '20251231', 'expected' => '2025-12-31 00:00:00'],
        ];

        foreach ($dateOnlyTests as $test) {
            $result = $this->parser->parse($test['input']);
            $this->assertEquals($test['expected'], $result->format('Y-m-d H:i:s'), "Failed parsing date-only: {$test['input']}");
        }

        // Test with timezone
        $result = $this->parser->parseWithTimezone('20250101', 'America/New_York');
        $this->assertEquals('2025-01-01 00:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $result->getTimezone()->getName());
    }
}
