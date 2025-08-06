<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Occurrence\Adapter;

use DateTimeImmutable;
use EphemeralTodos\Rruler\Occurrence\OccurrenceValidator;
use EphemeralTodos\Rruler\Testing\Behavior\TestOccurrenceGenerationBehavior;
use EphemeralTodos\Rruler\Testing\Behavior\TestRrulerBehavior;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class DefaultOccurrenceValidatorTest extends TestCase
{
    use TestRrulerBehavior;
    use TestOccurrenceGenerationBehavior;

    public function testImplementsOccurrenceValidatorInterface(): void
    {
        $this->assertInstanceOf(OccurrenceValidator::class, $this->testOccurrenceValidator);
    }

    #[DataProvider('provideValidOccurrenceData')]
    public function testIsValidOccurrenceReturnsTrue(string $rruleString, string $startDate, string $candidateDate): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);
        $candidate = new DateTimeImmutable($candidateDate);

        $result = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate);

        $this->assertTrue($result);
    }

    #[DataProvider('provideInvalidOccurrenceData')]
    public function testIsValidOccurrenceReturnsFalse(string $rruleString, string $startDate, string $candidateDate): void
    {
        $rrule = $this->testRruler->parse($rruleString);
        $start = new DateTimeImmutable($startDate);
        $candidate = new DateTimeImmutable($candidateDate);

        $result = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate);

        $this->assertFalse($result);
    }

    public function testValidateStartDateItself(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;COUNT=5');
        $start = new DateTimeImmutable('2025-01-01');

        $result = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $start);

        $this->assertTrue($result);
    }

    public function testValidateBeyondCountLimit(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;COUNT=3');
        $start = new DateTimeImmutable('2025-01-01');
        $candidate = new DateTimeImmutable('2025-01-04'); // 4th occurrence, but COUNT=3

        $result = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate);

        $this->assertFalse($result);
    }

    public function testValidateBeyondUntilLimit(): void
    {
        $rrule = $this->testRruler->parse('FREQ=DAILY;UNTIL=20250103T235959Z');
        $start = new DateTimeImmutable('2025-01-01');
        $candidate = new DateTimeImmutable('2025-01-04'); // Beyond UNTIL date

        $result = $this->testOccurrenceValidator->isValidOccurrence($rrule, $start, $candidate);

        $this->assertFalse($result);
    }

    public static function provideValidOccurrenceData(): array
    {
        return [
            'daily first occurrence' => [
                'FREQ=DAILY;COUNT=5',
                '2025-01-01',
                '2025-01-01',
            ],
            'daily second occurrence' => [
                'FREQ=DAILY;COUNT=5',
                '2025-01-01',
                '2025-01-02',
            ],
            'daily with interval' => [
                'FREQ=DAILY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                '2025-01-03',
            ],
            'weekly first occurrence' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01',
                '2025-01-01',
            ],
            'weekly second occurrence' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01',
                '2025-01-08',
            ],
            'weekly with interval' => [
                'FREQ=WEEKLY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                '2025-01-15',
            ],
            'until boundary date' => [
                'FREQ=DAILY;UNTIL=20250103T235959Z',
                '2025-01-01',
                '2025-01-03',
            ],
        ];
    }

    public static function provideInvalidOccurrenceData(): array
    {
        return [
            'daily wrong interval' => [
                'FREQ=DAILY;INTERVAL=2;COUNT=5',
                '2025-01-01',
                '2025-01-02', // Should be every 2 days
            ],
            'weekly wrong day' => [
                'FREQ=WEEKLY;COUNT=3',
                '2025-01-01', // Wednesday
                '2025-01-02',  // Thursday, not a weekly occurrence
            ],
            'before start date' => [
                'FREQ=DAILY;COUNT=5',
                '2025-01-01',
                '2024-12-31',
            ],
            'weekly wrong interval' => [
                'FREQ=WEEKLY;INTERVAL=2;COUNT=3',
                '2025-01-01',
                '2025-01-08', // Should be every 2 weeks
            ],
        ];
    }
}
