<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Integration\Documentation;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Exception\ParseException;
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Ical\IcalParser;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;
use EphemeralTodos\Rruler\Rruler;
use PHPUnit\Framework\TestCase;

/**
 * Integration test that validates all README.md code examples work correctly.
 *
 * This test ensures that documentation examples stay current with the API
 * and provides end-to-end validation of user workflows.
 */
final class DocumentationExampleValidationTest extends TestCase
{
    private Rruler $rruler;
    private DefaultOccurrenceGenerator $generator;

    protected function setUp(): void
    {
        $this->rruler = new Rruler();
        $this->generator = new DefaultOccurrenceGenerator();
    }

    public function testReadmeExtractabilityValidation(): void
    {
        // Validate that README.md contains extractable code examples
        $readmePath = __DIR__.'/../../../README.md';
        $this->assertFileExists($readmePath, 'README.md should exist for example extraction');

        $readmeContent = file_get_contents($readmePath);
        $this->assertNotEmpty($readmeContent, 'README.md should not be empty');

        // Validate that README contains expected PHP code blocks
        $this->assertStringContainsString('```php', $readmeContent, 'README should contain PHP code examples');
        $this->assertStringContainsString('$rruler = new Rruler()', $readmeContent, 'README should contain basic usage patterns');
        $this->assertStringContainsString('$generator = new DefaultOccurrenceGenerator()', $readmeContent, 'README should contain occurrence generation patterns');
    }

    public function testDocumentationExamplePatternConsistency(): void
    {
        // Validate that all documentation examples follow consistent patterns
        $expectedPatterns = [
            'Rruler instantiation' => 'new Rruler()',
            'Generator instantiation' => 'new DefaultOccurrenceGenerator()',
            'RRULE parsing' => '$rruler->parse(',
            'Occurrence generation' => '$generator->generateOccurrences(',
            'DateTime handling' => 'new DateTimeImmutable(',
        ];

        $readmeContent = file_get_contents(__DIR__.'/../../../README.md');

        foreach ($expectedPatterns as $description => $pattern) {
            $this->assertStringContainsString(
                $pattern,
                $readmeContent,
                "README should contain consistent {$description} pattern: {$pattern}"
            );
        }
    }

    public function testErrorHandlingExampleExtractability(): void
    {
        // Validate that error handling examples are extractable and testable
        $readmeContent = file_get_contents(__DIR__.'/../../../README.md');

        // Check for error handling patterns
        $this->assertStringContainsString('ValidationException', $readmeContent, 'README should show ValidationException handling');
        $this->assertStringContainsString('ParseException', $readmeContent, 'README should show ParseException handling');
        $this->assertStringContainsString('try {', $readmeContent, 'README should show try-catch patterns');

        // Test that the error handling function pattern works
        $testFunction = function (string $rruleString): ?\EphemeralTodos\Rruler\Rrule {
            try {
                $rruler = new Rruler();

                return $rruler->parse($rruleString);
            } catch (ValidationException $e) {
                return null;
            } catch (ParseException $e) {
                return null;
            }
        };

        // Valid RRULE should return Rrule object
        $validResult = $testFunction('FREQ=DAILY;COUNT=5');
        $this->assertInstanceOf(\EphemeralTodos\Rruler\Rrule::class, $validResult);

        // Invalid RRULE should return null
        $invalidResult = $testFunction('INVALID_RRULE');
        $this->assertNull($invalidResult);
    }

    public function testIcalendarExampleExtractability(): void
    {
        // Validate that iCalendar examples are extractable
        $readmeContent = file_get_contents(__DIR__.'/../../../README.md');

        $this->assertStringContainsString('IcalParser', $readmeContent, 'README should contain IcalParser examples');
        $this->assertStringContainsString('parse', $readmeContent, 'README should show parse usage');
        $this->assertStringContainsString('getSummary()', $readmeContent, 'README should show context extraction');

        // Test that the IcalParser pattern works as shown
        $icalParser = new IcalParser();
        $this->assertInstanceOf(IcalParser::class, $icalParser, 'IcalParser should be instantiable as shown in README');
    }

    public function testDateRangeFilteringExampleExtractability(): void
    {
        // Validate that date range filtering examples are extractable
        $readmeContent = file_get_contents(__DIR__.'/../../../README.md');

        $this->assertStringContainsString('generateOccurrencesInRange', $readmeContent, 'README should show date range filtering');

        // Test the date range pattern shown in README
        $rrule = $this->rruler->parse('FREQ=WEEKLY;BYDAY=MO');
        $start = new DateTimeImmutable('2024-01-01 09:00:00');
        $rangeStart = new DateTimeImmutable('2024-06-01');
        $rangeEnd = new DateTimeImmutable('2024-08-31');

        $occurrences = $this->generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd);
        $this->assertInstanceOf(\Generator::class, $occurrences, 'Date range filtering should return Generator');

        // Validate that the pattern produces expected results
        $results = iterator_to_array($occurrences);
        $this->assertNotEmpty($results, 'Date range filtering should produce occurrences');

        foreach ($results as $occurrence) {
            $this->assertGreaterThanOrEqual($rangeStart->getTimestamp(), $occurrence->getTimestamp());
            $this->assertLessThanOrEqual($rangeEnd->getTimestamp(), $occurrence->getTimestamp());
            $this->assertEquals(1, (int) $occurrence->format('N'), 'Should be Mondays as specified');
        }
    }

    public function testExampleCodeExecutability(): void
    {
        // Validate that key examples can be executed without modification

        // Test Quick Start example executability
        $rruler = new Rruler();
        $rrule = $rruler->parse('FREQ=DAILY;COUNT=5');
        $generator = new DefaultOccurrenceGenerator();
        $startDate = new DateTimeImmutable('2024-01-01 09:00:00');
        $occurrences = $generator->generateOccurrences($rrule, $startDate);

        $this->assertInstanceOf(\Generator::class, $occurrences, 'Quick Start example should return Generator');

        // Test that output matches documented expectations
        $results = iterator_to_array($occurrences);
        $this->assertCount(5, $results, 'Quick Start example should generate 5 occurrences');
        $this->assertEquals('2024-01-01 09:00:00', $results[0]->format('Y-m-d H:i:s'));
        $this->assertEquals('2024-01-05 09:00:00', $results[4]->format('Y-m-d H:i:s'));
    }

    public function testComplexExampleExecutability(): void
    {
        // Test complex yearly pattern from README
        $rrule = $this->rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;BYSETPOS=-1');
        $start = new DateTimeImmutable('2024-03-29 10:00:00');

        $occurrences = $this->generator->generateOccurrences($rrule, $start, 4);
        $results = iterator_to_array($occurrences);

        $this->assertCount(4, $results, 'Complex yearly pattern should generate 4 occurrences');

        // All should be Fridays
        foreach ($results as $result) {
            $this->assertEquals(5, (int) $result->format('N'), 'Should be Friday');
        }

        // Should be in specified months
        $expectedMonths = [3, 6, 9, 12];
        foreach ($results as $index => $result) {
            $this->assertEquals($expectedMonths[$index], (int) $result->format('n'), 'Should be in expected month');
        }
    }
}
