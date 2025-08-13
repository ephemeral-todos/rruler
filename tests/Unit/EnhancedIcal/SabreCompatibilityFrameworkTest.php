<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\EnhancedIcal;

use PHPUnit\Framework\TestCase;

final class SabreCompatibilityFrameworkTest extends TestCase
{
    public function testSabreVobjectIsAvailable(): void
    {
        $this->assertTrue(
            class_exists('Sabre\VObject\Reader'),
            'sabre/vobject should be available for compatibility testing'
        );
    }

    public function testSabreVobjectCanParseBasicCalendar(): void
    {
        if (!class_exists('Sabre\VObject\Reader')) {
            $this->markTestSkipped('sabre/vobject not available');
        }

        $basicIcal = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:Test\r\nBEGIN:VEVENT\r\nUID:test@example.com\r\nDTSTART:20240115T090000\r\nSUMMARY:Test Event\r\nEND:VEVENT\r\nEND:VCALENDAR";

        $vcalendar = \Sabre\VObject\Reader::read($basicIcal);
        $this->assertInstanceOf('Sabre\VObject\Component\VCalendar', $vcalendar);
        $this->assertCount(1, $vcalendar->VEVENT);
    }
}
