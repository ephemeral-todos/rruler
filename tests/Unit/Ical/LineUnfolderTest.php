<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\LineUnfolder;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LineUnfolderTest extends TestCase
{
    #[DataProvider('provideUnfoldingData')]
    public function testUnfoldsLinesAccordingToRfc5545(string $expected, string $input): void
    {
        $lineUnfolder = new LineUnfolder();
        $result = $lineUnfolder->unfold($input);

        $this->assertEquals($expected, $result);
    }

    #[DataProvider('provideFoldedLines')]
    public function testHandlesFoldedLinesWithSpacesAndTabs(array $expectedLines, string $input): void
    {
        $lineUnfolder = new LineUnfolder();
        $result = $lineUnfolder->unfoldToLines($input);

        $this->assertEquals($expectedLines, $result);
    }

    #[DataProvider('provideRealWorldExamples')]
    public function testHandlesRealWorldIcalendarContent(array $expectedLines, string $input): void
    {
        $lineUnfolder = new LineUnfolder();
        $result = $lineUnfolder->unfoldToLines($input);

        $this->assertEquals($expectedLines, $result);
    }

    public function testEmptyInputReturnsEmptyString(): void
    {
        $lineUnfolder = new LineUnfolder();
        $result = $lineUnfolder->unfold('');

        $this->assertEquals('', $result);
    }

    public function testEmptyInputToLinesReturnsEmptyArray(): void
    {
        $lineUnfolder = new LineUnfolder();
        $result = $lineUnfolder->unfoldToLines('');

        $this->assertEquals([], $result);
    }

    public static function provideUnfoldingData(): array
    {
        return [
            'no folding needed' => [
                'SUMMARY:Simple event',
                'SUMMARY:Simple event',
            ],
            'single folded line with space' => [
                'SUMMARY:This is a very long event title that exceeds the 75 character limit',
                "SUMMARY:This is a very long event title that exceeds the 75 character\r\n limit",
            ],
            'single folded line with tab' => [
                'SUMMARY:This is a very long event title that exceeds the 75 character limit',
                "SUMMARY:This is a very long event title that exceeds the 75 character\r\n\tlimit",
            ],
            'multiple folded lines' => [
                'DESCRIPTION:This is an extremely long description that will definitely need to be folded across multiple lines to comply with RFC 5545 requirements',
                "DESCRIPTION:This is an extremely long description that will definitely\r\n need to be folded across multiple lines to comply with RFC 5545\r\n requirements",
            ],
            'crlf line endings' => [
                'SUMMARY:Event with CRLF',
                'SUMMARY:Event with CRLF',
            ],
            'mixed line endings' => [
                'SUMMARY:This is a folded line with mixed endings',
                "SUMMARY:This is a folded line with\r\n mixed endings",
            ],
        ];
    }

    public static function provideFoldedLines(): array
    {
        return [
            'single unfolded line' => [
                ['SUMMARY:Simple event'],
                'SUMMARY:Simple event',
            ],
            'multiple unfolded lines' => [
                ['SUMMARY:Event 1', 'DTSTART:20250107T100000Z'],
                "SUMMARY:Event 1\r\nDTSTART:20250107T100000Z",
            ],
            'folded and unfolded mixed' => [
                ['SUMMARY:This is a very long event title that exceeds the 75 character limit', 'DTSTART:20250107T100000Z'],
                "SUMMARY:This is a very long event title that exceeds the 75 character\r\n limit\r\nDTSTART:20250107T100000Z",
            ],
            'multiple folded lines' => [
                ['DESCRIPTION:This is an extremely long description that will definitely need to be folded across multiple lines to comply with RFC 5545 requirements'],
                "DESCRIPTION:This is an extremely long description that will definitely\r\n need to be folded across multiple lines to comply with RFC 5545\r\n requirements",
            ],
        ];
    }

    public static function provideRealWorldExamples(): array
    {
        return [
            'basic VEVENT component' => [
                [
                    'BEGIN:VEVENT',
                    'SUMMARY:Weekly Team Meeting',
                    'DTSTART:20250107T100000Z',
                    'RRULE:FREQ=WEEKLY;BYDAY=TU',
                    'END:VEVENT',
                ],
                "BEGIN:VEVENT\r\nSUMMARY:Weekly Team Meeting\r\nDTSTART:20250107T100000Z\r\nRRULE:FREQ=WEEKLY;BYDAY=TU\r\nEND:VEVENT",
            ],
            'VEVENT with folded DESCRIPTION' => [
                [
                    'BEGIN:VEVENT',
                    'SUMMARY:Project Planning Session',
                    'DESCRIPTION:This is a comprehensive project planning session where we will discuss milestones, deliverables, and resource allocation for Q1',
                    'DTSTART:20250107T140000Z',
                    'END:VEVENT',
                ],
                "BEGIN:VEVENT\r\nSUMMARY:Project Planning Session\r\nDESCRIPTION:This is a comprehensive project planning session where we\r\n will discuss milestones, deliverables, and resource allocation for Q1\r\nDTSTART:20250107T140000Z\r\nEND:VEVENT",
            ],
            'VTODO with complex folding' => [
                [
                    'BEGIN:VTODO',
                    'SUMMARY:Complete quarterly reporting tasks including financial analysis and performance metrics',
                    'DUE:20250131T235959Z',
                    'RRULE:FREQ=MONTHLY;BYMONTHDAY=31',
                    'END:VTODO',
                ],
                "BEGIN:VTODO\r\nSUMMARY:Complete quarterly reporting tasks including financial\r\n analysis and performance metrics\r\nDUE:20250131T235959Z\r\nRRULE:FREQ=MONTHLY;BYMONTHDAY=31\r\nEND:VTODO",
            ],
        ];
    }
}
