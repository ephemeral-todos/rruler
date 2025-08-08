<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

final class ReadmeContentValidationTest extends TestCase
{
    private string $readmePath;

    protected function setUp(): void
    {
        $this->readmePath = dirname(__DIR__, 3).'/README.md';
    }

    public function testReadmeFileExists(): void
    {
        $this->assertFileExists($this->readmePath, 'README.md file should exist');
    }

    public function testReadmeHasRequiredSections(): void
    {
        if (!file_exists($this->readmePath)) {
            $this->markTestSkipped('README.md does not exist yet');
        }

        $content = file_get_contents($this->readmePath);
        $this->assertNotFalse($content, 'README.md should be readable');

        // Test for required header sections
        $this->assertStringContainsString('# Rruler', $content, 'README should have main title');
        $this->assertStringContainsString('## Installation', $content, 'README should have installation section');
        $this->assertStringContainsString('## Usage', $content, 'README should have usage section');
    }

    public function testReadmeHasValueProposition(): void
    {
        if (!file_exists($this->readmePath)) {
            $this->markTestSkipped('README.md does not exist yet');
        }

        $content = file_get_contents($this->readmePath);
        
        // Test for key value proposition elements
        $this->assertStringContainsString('RFC 5545', $content, 'README should mention RFC 5545 compliance');
        $this->assertStringContainsString('RRULE', $content, 'README should mention RRULE parsing capability');
        $this->assertStringContainsString('PHP 8.3', $content, 'README should specify PHP version requirement');
    }

    public function testReadmeHasPositioning(): void
    {
        if (!file_exists($this->readmePath)) {
            $this->markTestSkipped('README.md does not exist yet');
        }

        $content = file_get_contents($this->readmePath);
        
        // Test for positioning elements
        $this->assertStringContainsString('sabre/dav', $content, 'README should mention sabre/dav for comparison');
        $this->assertStringContainsString('focused', $content, 'README should emphasize focused scope');
    }

    public function testReadmeHasWorkingCodeExamples(): void
    {
        if (!file_exists($this->readmePath)) {
            $this->markTestSkipped('README.md does not exist yet');
        }

        $content = file_get_contents($this->readmePath);
        
        // Test for code example structure
        $this->assertStringContainsString('```php', $content, 'README should have PHP code examples');
        $this->assertStringContainsString('composer require', $content, 'README should have composer installation command');
        $this->assertStringContainsString('use EphemeralTodos\Rruler', $content, 'README should show proper namespace usage');
    }

    public function testReadmeIsReasonableLength(): void
    {
        if (!file_exists($this->readmePath)) {
            $this->markTestSkipped('README.md does not exist yet');
        }

        $content = file_get_contents($this->readmePath);
        $lineCount = count(explode("\n", $content));
        
        $this->assertGreaterThan(50, $lineCount, 'README should be substantial (>50 lines)');
        $this->assertLessThan(300, $lineCount, 'README should be focused (<300 lines)');
    }
}