<?php

declare(strict_types=1);

namespace EphemeralTodos\Rruler\Tests\Unit\TestCleanup;

use EphemeralTodos\Rruler\Tests\TestCleanup\AssertionPatternAnalyzer;
use PHPUnit\Framework\TestCase;

/**
 * Tests for assertion pattern analysis utilities.
 *
 * This test validates the utility that identifies string-content assertions
 * that should be replaced with behavioral assertions focusing on functional outcomes.
 */
class AssertionPatternAnalysisTest extends TestCase
{
    private AssertionPatternAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new AssertionPatternAnalyzer();
    }

    /**
     * @test
     */
    public function detectsStringComparisonAssertions(): void
    {
        $testContent = '
        public function testSomething(): void
        {
            $this->assertEquals("expected string", $actual);
            $this->assertSame("another string", $result);
        }';

        $patterns = $this->analyzer->findStringAssertions($testContent);

        $this->assertCount(2, $patterns);
        $this->assertStringContainsString('assertEquals("expected string"', $patterns[0]['line']);
        $this->assertStringContainsString('assertSame("another string"', $patterns[1]['line']);
    }

    /**
     * @test
     */
    public function detectsToStringMethodUsage(): void
    {
        $testContent = '
        public function testToString(): void
        {
            $this->assertEquals("formatted output", $object->toString());
        }';

        $patterns = $this->analyzer->findToStringUsage($testContent);

        $this->assertCount(1, $patterns);
        $this->assertStringContainsString('toString()', $patterns[0]['line']);
    }

    /**
     * @test
     */
    public function identifiesStringContainsAssertions(): void
    {
        $testContent = '
        public function testContains(): void
        {
            $this->assertStringContains("substring", $haystack);
        }';

        $patterns = $this->analyzer->findStringContainsAssertions($testContent);

        $this->assertCount(1, $patterns);
        $this->assertStringContainsString('assertStringContains', $patterns[0]['line']);
    }

    /**
     * @test
     */
    public function suggestsBehavioralAlternatives(): void
    {
        $stringAssertion = '$this->assertEquals("DAILY", $frequency);';

        $suggestions = $this->analyzer->suggestBehavioralAlternative($stringAssertion);

        $this->assertIsArray($suggestions);
        $this->assertNotEmpty($suggestions);
        $this->assertArrayHasKey('pattern', $suggestions);
        $this->assertArrayHasKey('suggestion', $suggestions);
    }

    /**
     * @test
     */
    public function classifiesAssertionTypes(): void
    {
        $functionalAssertion = '$this->assertTrue($parser->isValid());';
        $stringAssertion = '$this->assertEquals("value", $result);';

        $this->assertTrue($this->analyzer->isBehavioralAssertion($functionalAssertion));
        $this->assertFalse($this->analyzer->isBehavioralAssertion($stringAssertion));
    }

    /**
     * @test
     */
    public function analyzesFileForAssertionPatterns(): void
    {
        $testFilePath = __DIR__.'/sample_test_file.php';

        // Create a temporary test file for analysis
        $sampleContent = '<?php
        class SampleTest extends TestCase
        {
            public function testStringComparison(): void
            {
                $this->assertEquals("expected", $actual);
                $this->assertTrue($condition);
                $this->assertSame("another", $result->toString());
            }
        }';

        file_put_contents($testFilePath, $sampleContent);

        try {
            $analysis = $this->analyzer->analyzeFile($testFilePath);

            $this->assertArrayHasKey('string_assertions', $analysis);
            $this->assertArrayHasKey('behavioral_assertions', $analysis);
            $this->assertArrayHasKey('toString_usage', $analysis);
            $this->assertArrayHasKey('total_assertions', $analysis);

            $this->assertGreaterThan(0, $analysis['string_assertions']);
            $this->assertGreaterThan(0, $analysis['behavioral_assertions']);
        } finally {
            unlink($testFilePath);
        }
    }

    /**
     * @test
     */
    public function generatesSummaryReport(): void
    {
        $analysisData = [
            'total_files' => 5,
            'files_with_string_assertions' => 3,
            'total_string_assertions' => 15,
            'total_behavioral_assertions' => 25,
            'improvement_opportunities' => [
                'Replace toString() comparisons with behavioral validation',
                'Convert string format assertions to functional outcome checks',
            ],
        ];

        $report = $this->analyzer->generateSummaryReport($analysisData);

        $this->assertIsString($report);
        $this->assertStringContainsString('Assertion Pattern Analysis Summary', $report);
        $this->assertStringContainsString('Files analyzed: 5', $report);
        $this->assertStringContainsString('Total string assertions found: 15', $report);
        $this->assertStringContainsString('Improvement Opportunities:', $report);
    }
}
