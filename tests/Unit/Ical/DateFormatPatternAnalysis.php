<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\IcalParser;
use PHPUnit\Framework\TestCase;

/**
 * Analysis of date format patterns from collected test data files.
 *
 * This class analyzes various iCalendar files to identify date/time format
 * patterns used by different calendar applications.
 */
final class DateFormatPatternAnalysis extends TestCase
{
    private IcalParser $parser;

    protected function setUp(): void
    {
        $this->parser = new IcalParser();
    }

    /**
     * Test pattern analysis from synthetic test data.
     */
    public function testSyntheticDataPatterns(): void
    {
        $testFile = __DIR__.'/../../data/enhanced-ical/synthetic/complex-mixed-components.ics';

        if (!file_exists($testFile)) {
            $this->markTestSkipped('Synthetic test data file not found');
        }

        $content = file_get_contents($testFile);
        $this->assertNotFalse($content);

        // Extract date/time patterns from the file
        $patterns = $this->extractDateTimePatterns($content);

        // Verify we found various patterns
        $this->assertGreaterThan(0, count($patterns), 'Should find date/time patterns in synthetic data');

        // Log patterns for analysis
        foreach ($patterns as $pattern => $occurrences) {
            echo "Pattern: $pattern, Occurrences: $occurrences\n";
        }
    }

    /**
     * Analyze common date format patterns found in real-world scenarios.
     */
    public function testCommonDateFormatPatterns(): void
    {
        $commonPatterns = [
            // Standard RFC 5545 patterns
            'DATE-ONLY' => [
                '20240115',
                '20241225',
                '20250101',
            ],

            // Local time patterns
            'LOCAL-DATETIME' => [
                '20240115T090000',
                '20240102T140000',
                '20241031T020000',
            ],

            // UTC time patterns
            'UTC-DATETIME' => [
                '20240101T120000Z',
                '20240105T143000Z',
                '20241231T235959Z',
            ],

            // Timezone-aware patterns (with TZID parameter)
            'TIMEZONE-DATETIME' => [
                'DTSTART;TZID=America/New_York:20240115T090000',
                'DTEND;TZID=Europe/London:20240102T140000',
                'DUE;TZID=America/Los_Angeles:20241031T170000',
            ],
        ];

        foreach ($commonPatterns as $category => $examples) {
            foreach ($examples as $example) {
                // For non-property examples, test direct parsing
                if (!str_contains($example, ':')) {
                    $this->assertDateTimePattern($example, $category);
                } else {
                    // For property examples, test pattern recognition
                    $this->assertPropertyPattern($example, $category);
                }
            }
        }
    }

    /**
     * Test Microsoft Outlook specific patterns.
     */
    public function testMicrosoftOutlookPatterns(): void
    {
        // Microsoft Outlook typically follows RFC 5545 but may have specific patterns
        $outlookPatterns = [
            // Standard Outlook export patterns
            '20250315T143000',     // Local time
            '20250315T143000Z',    // UTC time
            '20250315',            // Date only

            // Common Outlook timezone patterns
            'DTSTART;TZID=Eastern Standard Time:20250315T143000',
            'DTSTART;TZID=Pacific Standard Time:20250315T100000',
        ];

        foreach ($outlookPatterns as $pattern) {
            if (!str_contains($pattern, ':')) {
                $this->assertDateTimePattern($pattern, 'OUTLOOK');
            } else {
                $this->assertPropertyPattern($pattern, 'OUTLOOK');
            }
        }
    }

    /**
     * Test Google Calendar specific patterns.
     */
    public function testGoogleCalendarPatterns(): void
    {
        // Google Calendar follows RFC 5545 strictly
        $googlePatterns = [
            // Standard Google Calendar patterns
            '20250601T090000',     // Local time
            '20250601T090000Z',    // UTC time
            '20250601',            // Date only

            // Google uses standard timezone identifiers
            'DTSTART;TZID=America/New_York:20250601T090000',
            'DTSTART;TZID=Europe/London:20250601T140000',
        ];

        foreach ($googlePatterns as $pattern) {
            if (!str_contains($pattern, ':')) {
                $this->assertDateTimePattern($pattern, 'GOOGLE');
            } else {
                $this->assertPropertyPattern($pattern, 'GOOGLE');
            }
        }
    }

    /**
     * Test Apple Calendar specific patterns.
     */
    public function testAppleCalendarPatterns(): void
    {
        // Apple Calendar patterns
        $applePatterns = [
            // Standard Apple Calendar patterns
            '20251225T000000',     // Local time (midnight)
            '20251225T235959Z',    // UTC time (end of day)
            '20251225',            // Date only

            // Apple uses standard timezone identifiers
            'DTSTART;TZID=America/Los_Angeles:20251225T000000',
            'DTSTART;TZID=Australia/Sydney:20251225T120000',
        ];

        foreach ($applePatterns as $pattern) {
            if (!str_contains($pattern, ':')) {
                $this->assertDateTimePattern($pattern, 'APPLE');
            } else {
                $this->assertPropertyPattern($pattern, 'APPLE');
            }
        }
    }

    /**
     * Extract date/time patterns from iCalendar content.
     */
    private function extractDateTimePatterns(string $content): array
    {
        $patterns = [];

        // Match various date/time patterns
        $regexes = [
            // Date-only pattern
            '/\b(\d{8})\b/' => 'DATE-ONLY',

            // Local datetime pattern
            '/\b(\d{8}T\d{6})\b/' => 'LOCAL-DATETIME',

            // UTC datetime pattern
            '/\b(\d{8}T\d{6}Z)\b/' => 'UTC-DATETIME',

            // Property with timezone pattern
            '/([A-Z]+;TZID=[^:]+:\d{8}T?\d{0,6}Z?)/' => 'TIMEZONE-PROPERTY',
        ];

        foreach ($regexes as $regex => $type) {
            preg_match_all($regex, $content, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $patterns[$match] = ($patterns[$match] ?? 0) + 1;
                }
            }
        }

        return $patterns;
    }

    /**
     * Assert that a date/time string follows expected pattern.
     */
    private function assertDateTimePattern(string $dateTime, string $category): void
    {
        // Validate the pattern is correctly formatted
        $validPatterns = [
            '/^\d{8}$/',                    // DATE-ONLY
            '/^\d{8}T\d{6}$/',             // LOCAL-DATETIME
            '/^\d{8}T\d{6}Z$/',            // UTC-DATETIME
        ];

        $isValid = false;
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $dateTime)) {
                $isValid = true;
                break;
            }
        }

        $this->assertTrue($isValid, "Invalid date/time pattern in $category: $dateTime");
    }

    /**
     * Assert that a property string follows expected pattern.
     */
    private function assertPropertyPattern(string $property, string $category): void
    {
        // Validate property patterns
        $propertyPatterns = [
            '/^[A-Z]+;TZID=[^:]+:\d{8}T?\d{0,6}Z?$/',  // TIMEZONE-DATETIME
            '/^[A-Z]+:\d{8}T?\d{0,6}Z?$/',             // SIMPLE-DATETIME
        ];

        $isValid = false;
        foreach ($propertyPatterns as $pattern) {
            if (preg_match($pattern, $property)) {
                $isValid = true;
                break;
            }
        }

        $this->assertTrue($isValid, "Invalid property pattern in $category: $property");
    }

    /**
     * Test edge cases in date format patterns.
     */
    public function testDateFormatEdgeCases(): void
    {
        $edgeCases = [
            // Leap year edge cases
            '20240229T120000',     // Leap year Feb 29
            '20240229',            // Leap year date only

            // Year boundaries
            '20231231T235959Z',    // End of year
            '20240101T000000Z',    // Start of year

            // Month boundaries
            '20240131T120000',     // End of January
            '20240201T120000',     // Start of February

            // Time boundaries
            '20240615T000000',     // Midnight
            '20240615T235959',     // End of day
            '20240615T120000',     // Noon
        ];

        foreach ($edgeCases as $edgeCase) {
            $this->assertDateTimePattern($edgeCase, 'EDGE-CASE');
        }
    }
}
