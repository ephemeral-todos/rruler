<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\Documentation;

use PHPUnit\Framework\TestCase;

/**
 * Tests for phpDocumentor configuration validation.
 *
 * Ensures the phpDocumentor configuration file is valid and properly structured
 * for generating comprehensive API documentation.
 */
class PhpDocConfigValidationTest extends TestCase
{
    private string $configPath;

    protected function setUp(): void
    {
        $this->configPath = dirname(__DIR__, 3).'/phpdoc.xml';
    }

    public function testPhpDocConfigurationFileExists(): void
    {
        $this->assertFileExists(
            $this->configPath,
            'phpDocumentor configuration file should exist in project root'
        );
    }

    public function testPhpDocConfigurationIsValidXml(): void
    {
        if (!file_exists($this->configPath)) {
            $this->markTestSkipped('phpDocumentor configuration file does not exist yet');
        }

        $xmlContent = file_get_contents($this->configPath);
        $this->assertNotFalse($xmlContent, 'Configuration file should be readable');

        // Validate XML structure
        $dom = new \DOMDocument();
        $dom->loadXML($xmlContent);

        $this->assertInstanceOf(\DOMDocument::class, $dom);
        $this->assertEmpty(libxml_get_errors(), 'Configuration should be valid XML');
    }

    public function testPhpDocConfigurationHasRequiredElements(): void
    {
        if (!file_exists($this->configPath)) {
            $this->markTestSkipped('phpDocumentor configuration file does not exist yet');
        }

        $xml = simplexml_load_file($this->configPath);
        $this->assertNotFalse($xml, 'Configuration should be valid XML');

        // Check for required elements
        $this->assertNotNull($xml->title, 'Configuration should have a title element');
        $this->assertNotNull($xml->paths, 'Configuration should have paths element');
        $this->assertNotNull($xml->version, 'Configuration should have version element');
    }

    public function testPhpDocConfigurationIncludesSourceDirectory(): void
    {
        if (!file_exists($this->configPath)) {
            $this->markTestSkipped('phpDocumentor configuration file does not exist yet');
        }

        $xml = simplexml_load_file($this->configPath);
        $this->assertNotFalse($xml, 'Configuration should be valid XML');

        $sourcePaths = $xml->xpath('//path[text()="src"]');
        $this->assertNotEmpty(
            $sourcePaths,
            'Configuration should include src directory in paths'
        );
    }

    public function testPhpDocConfigurationHasOutputDirectory(): void
    {
        if (!file_exists($this->configPath)) {
            $this->markTestSkipped('phpDocumentor configuration file does not exist yet');
        }

        $xml = simplexml_load_file($this->configPath);
        $this->assertNotFalse($xml, 'Configuration should be valid XML');

        $outputPath = $xml->xpath('//version/output');
        $this->assertNotEmpty(
            $outputPath,
            'Configuration should specify output directory'
        );
    }

    public function testGeneratedDocumentationDirectoryIsAccessible(): void
    {
        $outputDir = dirname(__DIR__, 3).'/docs';

        if (!is_dir($outputDir)) {
            $this->markTestSkipped('Documentation output directory does not exist yet');
        }

        $this->assertDirectoryIsReadable(
            $outputDir,
            'Generated documentation directory should be readable'
        );

        $indexFile = $outputDir.'/index.html';
        if (file_exists($indexFile)) {
            $this->assertFileIsReadable($indexFile, 'Documentation index should be readable');
        }
    }

    public function testPhpDocumentorBinaryIsAvailable(): void
    {
        $composerLockPath = dirname(__DIR__, 3).'/composer.lock';

        if (!file_exists($composerLockPath)) {
            $this->markTestSkipped('composer.lock file does not exist');
        }

        $composerLock = json_decode(file_get_contents($composerLockPath), true);
        $this->assertIsArray($composerLock, 'composer.lock should contain valid JSON');

        $phpDocInstalled = false;
        foreach ($composerLock['packages-dev'] ?? [] as $package) {
            if ($package['name'] === 'phpdocumentor/phpdocumentor') {
                $phpDocInstalled = true;
                break;
            }
        }

        $this->assertTrue(
            $phpDocInstalled,
            'phpDocumentor should be installed as development dependency'
        );
    }
}
