<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\EnhancedIcal;

use PHPUnit\Framework\TestCase;

final class TestDataInfrastructureTest extends TestCase
{
    private string $testDataPath;

    protected function setUp(): void
    {
        $this->testDataPath = dirname(__DIR__, 2).'/data/enhanced-ical';
    }

    public function testTestDataDirectoryExists(): void
    {
        $this->assertDirectoryExists($this->testDataPath, 'Enhanced iCalendar test data directory should exist');
    }

    public function testMicrosoftOutlookDataDirectoryExists(): void
    {
        $outlookPath = $this->testDataPath.'/microsoft-outlook';
        $this->assertDirectoryExists($outlookPath, 'Microsoft Outlook test data directory should exist');
    }

    public function testGoogleCalendarDataDirectoryExists(): void
    {
        $googlePath = $this->testDataPath.'/google-calendar';
        $this->assertDirectoryExists($googlePath, 'Google Calendar test data directory should exist');
    }

    public function testAppleCalendarDataDirectoryExists(): void
    {
        $applePath = $this->testDataPath.'/apple-calendar';
        $this->assertDirectoryExists($applePath, 'Apple Calendar test data directory should exist');
    }

    public function testSyntheticDataDirectoryExists(): void
    {
        $syntheticPath = $this->testDataPath.'/synthetic';
        $this->assertDirectoryExists($syntheticPath, 'Synthetic test data directory should exist');
    }

    public function testMicrosoftOutlookSampleFilesExist(): void
    {
        $outlookPath = $this->testDataPath.'/microsoft-outlook';
        if (!is_dir($outlookPath)) {
            $this->markTestSkipped('Microsoft Outlook test data directory does not exist');
        }

        $files = glob($outlookPath.'/*.ics');
        $this->assertGreaterThanOrEqual(5, count($files), 'Should have at least 5 Microsoft Outlook test files');

        foreach ($files as $file) {
            $this->assertFileIsReadable($file, 'Outlook test file should be readable: '.basename($file));
            $this->assertStringContainsString('VCALENDAR', file_get_contents($file),
                'File should contain VCALENDAR: '.basename($file));
        }
    }

    public function testGoogleCalendarSampleFilesExist(): void
    {
        $googlePath = $this->testDataPath.'/google-calendar';
        if (!is_dir($googlePath)) {
            $this->markTestSkipped('Google Calendar test data directory does not exist');
        }

        $files = glob($googlePath.'/*.ics');
        $this->assertGreaterThanOrEqual(5, count($files), 'Should have at least 5 Google Calendar test files');

        foreach ($files as $file) {
            $this->assertFileIsReadable($file, 'Google Calendar test file should be readable: '.basename($file));
            $this->assertStringContainsString('VCALENDAR', file_get_contents($file),
                'File should contain VCALENDAR: '.basename($file));
        }
    }

    public function testAppleCalendarSampleFilesExist(): void
    {
        $applePath = $this->testDataPath.'/apple-calendar';
        if (!is_dir($applePath)) {
            $this->markTestSkipped('Apple Calendar test data directory does not exist');
        }

        $files = glob($applePath.'/*.ics');
        $this->assertGreaterThanOrEqual(5, count($files), 'Should have at least 5 Apple Calendar test files');

        foreach ($files as $file) {
            $this->assertFileIsReadable($file, 'Apple Calendar test file should be readable: '.basename($file));
            $this->assertStringContainsString('VCALENDAR', file_get_contents($file),
                'File should contain VCALENDAR: '.basename($file));
        }
    }

    public function testSyntheticTestFilesExist(): void
    {
        $syntheticPath = $this->testDataPath.'/synthetic';
        if (!is_dir($syntheticPath)) {
            $this->markTestSkipped('Synthetic test data directory does not exist');
        }

        $files = glob($syntheticPath.'/*.ics');
        $this->assertGreaterThanOrEqual(3, count($files), 'Should have at least 3 synthetic test files');

        foreach ($files as $file) {
            $this->assertFileIsReadable($file, 'Synthetic test file should be readable: '.basename($file));
            $this->assertStringContainsString('VCALENDAR', file_get_contents($file),
                'File should contain VCALENDAR: '.basename($file));
        }
    }

    public function testTestFilesHaveMultipleComponents(): void
    {
        $testDirectories = [
            'microsoft-outlook',
            'google-calendar',
            'apple-calendar',
        ];

        foreach ($testDirectories as $directory) {
            $path = $this->testDataPath.'/'.$directory;
            if (!is_dir($path)) {
                continue;
            }

            $files = glob($path.'/*.ics');
            foreach ($files as $file) {
                $content = file_get_contents($file);

                // Count VEVENT and VTODO components
                $veventCount = preg_match_all('/BEGIN:VEVENT/i', $content);
                $vtodoCount = preg_match_all('/BEGIN:VTODO/i', $content);
                $totalComponents = $veventCount + $vtodoCount;

                $this->assertGreaterThanOrEqual(10, $totalComponents,
                    'File should have at least 10 components: '.basename($file).
                    " (found {$totalComponents}: {$veventCount} VEVENT, {$vtodoCount} VTODO)");
            }
        }
    }
}
