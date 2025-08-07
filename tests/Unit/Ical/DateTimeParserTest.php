<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Ical;

use EphemeralTodos\Rruler\Ical\DateTimeParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DateTimeParserTest extends TestCase
{
    #[DataProvider('provideValidDateTimes')]
    public function testParsesValidDateTimes(string $input, \DateTimeInterface $expected): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parse($input);

        $this->assertEquals($expected->format('Y-m-d H:i:s T'), $result->format('Y-m-d H:i:s T'));
    }

    #[DataProvider('provideValidDates')]
    public function testParsesValidDates(string $input, \DateTimeInterface $expected): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parse($input);

        $this->assertEquals($expected->format('Y-m-d'), $result->format('Y-m-d'));
        $this->assertEquals('00:00:00', $result->format('H:i:s'));
    }

    #[DataProvider('provideTimestampsWithTimezone')]
    public function testParsesTimestampsWithTimezone(string $input, string $timezone, \DateTimeInterface $expected): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parseWithTimezone($input, $timezone);

        $this->assertEquals($expected->format('Y-m-d H:i:s'), $result->format('Y-m-d H:i:s'));
        $this->assertEquals($timezone, $result->getTimezone()->getName());
    }

    #[DataProvider('provideInvalidFormats')]
    public function testThrowsExceptionForInvalidFormats(string $input): void
    {
        $parser = new DateTimeParser();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid iCalendar date/time format');

        $parser->parse($input);
    }

    public function testHandlesUtcTimestamps(): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parse('20250107T100000Z');

        $this->assertEquals('2025-01-07 10:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertEquals('UTC', $result->getTimezone()->getName());
    }

    public function testHandlesLocalTimestamps(): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parse('20250107T100000');

        $this->assertEquals('2025-01-07 10:00:00', $result->format('Y-m-d H:i:s'));
        // Should use system default timezone
    }

    public function testHandlesDateOnly(): void
    {
        $parser = new DateTimeParser();
        $result = $parser->parse('20250107');

        $this->assertEquals('2025-01-07 00:00:00', $result->format('Y-m-d H:i:s'));
    }

    public function testDefaultTimezoneForParseWithTimezone(): void
    {
        $parser = new DateTimeParser();

        // Parsing date-only should work with timezone
        $result = $parser->parseWithTimezone('20250107', 'America/New_York');

        $this->assertEquals('2025-01-07 00:00:00', $result->format('Y-m-d H:i:s'));
        $this->assertEquals('America/New_York', $result->getTimezone()->getName());
    }

    public function testPreservesTimezoneFromInput(): void
    {
        $parser = new DateTimeParser();

        // UTC timestamp should stay UTC even with timezone parameter
        $result = $parser->parseWithTimezone('20250107T100000Z', 'America/New_York');

        $this->assertEquals('UTC', $result->getTimezone()->getName());
    }

    public static function provideValidDateTimes(): array
    {
        return [
            'UTC timestamp' => [
                '20250107T100000Z',
                new \DateTimeImmutable('2025-01-07 10:00:00 UTC'),
            ],
            'local timestamp' => [
                '20250107T100000',
                new \DateTimeImmutable('2025-01-07 10:00:00'),
            ],
            'timestamp with seconds' => [
                '20250107T100030Z',
                new \DateTimeImmutable('2025-01-07 10:00:30 UTC'),
            ],
            'end of year timestamp' => [
                '20241231T235959Z',
                new \DateTimeImmutable('2024-12-31 23:59:59 UTC'),
            ],
            'midnight timestamp' => [
                '20250107T000000Z',
                new \DateTimeImmutable('2025-01-07 00:00:00 UTC'),
            ],
        ];
    }

    public static function provideValidDates(): array
    {
        return [
            'basic date' => [
                '20250107',
                new \DateTimeImmutable('2025-01-07 00:00:00'),
            ],
            'leap year date' => [
                '20240229',
                new \DateTimeImmutable('2024-02-29 00:00:00'),
            ],
            'end of year date' => [
                '20241231',
                new \DateTimeImmutable('2024-12-31 00:00:00'),
            ],
            'beginning of year date' => [
                '20250101',
                new \DateTimeImmutable('2025-01-01 00:00:00'),
            ],
        ];
    }

    public static function provideTimestampsWithTimezone(): array
    {
        return [
            'Eastern time' => [
                '20250107T100000',
                'America/New_York',
                new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/New_York')),
            ],
            'Pacific time' => [
                '20250107T100000',
                'America/Los_Angeles',
                new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('America/Los_Angeles')),
            ],
            'European time' => [
                '20250107T100000',
                'Europe/London',
                new \DateTimeImmutable('2025-01-07 10:00:00', new \DateTimeZone('Europe/London')),
            ],
            'date with timezone' => [
                '20250107',
                'Asia/Tokyo',
                new \DateTimeImmutable('2025-01-07 00:00:00', new \DateTimeZone('Asia/Tokyo')),
            ],
        ];
    }

    public static function provideInvalidFormats(): array
    {
        return [
            'empty string' => [''],
            'invalid characters' => ['2025-01-07'],
            'too short' => ['2025010'],
            'invalid month' => ['20251307'],
            'invalid day' => ['20250132'],
            'invalid hour' => ['20250107T250000Z'],
            'invalid minute' => ['20250107T106000Z'],
            'invalid second' => ['20250107T100060Z'],
            'mixed format' => ['2025/01/07T10:00:00'],
            'malformed timestamp' => ['20250107T10000'],
            'partial timestamp' => ['20250107T10'],
        ];
    }
}
