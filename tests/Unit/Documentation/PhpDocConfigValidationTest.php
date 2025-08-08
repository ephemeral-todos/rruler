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
}
