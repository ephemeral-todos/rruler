<?php

declare(strict_types=1);

/**
 * Analyze edge case tests for consolidation opportunities.
 *
 * This script identifies overly specific edge case tests that can be
 * consolidated into broader behavioral validation tests.
 */

$testsDir = dirname(__DIR__) . '/tests';
$consolidationOpportunities = [];

// Analyze leap year test patterns
echo "=== LEAP YEAR TEST CONSOLIDATION OPPORTUNITIES ===\n\n";
$leapYearTests = findTestsWithPattern($testsDir, 'Leap');
foreach ($leapYearTests as $file => $methods) {
    echo "File: {$file}\n";
    foreach ($methods as $method) {
        echo "  - {$method}\n";
    }
    echo "\n";
}

// Analyze boundary test patterns
echo "=== BOUNDARY CONDITION TEST CONSOLIDATION OPPORTUNITIES ===\n\n";
$boundaryTests = findTestsWithPattern($testsDir, 'Boundary|YearBoundary|MonthBoundary');
foreach ($boundaryTests as $file => $methods) {
    echo "File: {$file}\n";
    foreach ($methods as $method) {
        echo "  - {$method}\n";
    }
    echo "\n";
}

// Analyze property extraction edge cases
echo "=== PROPERTY EXTRACTION EDGE CASE CONSOLIDATION ===\n\n";
$propertyTests = findTestsWithPattern($testsDir, 'Missing|Malformed|Empty|Duplicate');
foreach ($propertyTests as $file => $methods) {
    echo "File: {$file}\n";
    foreach ($methods as $method) {
        echo "  - {$method}\n";
    }
    echo "\n";
}

// Specific consolidation recommendations
echo "=== CONSOLIDATION RECOMMENDATIONS ===\n\n";

// Recommendation 1: Leap Year Tests
echo "1. LEAP YEAR BEHAVIOR CONSOLIDATION\n";
echo "   Target: BoundaryConditionTest leap year methods\n";
echo "   Current: 3 separate methods testing February leap year scenarios\n";
echo "   Proposed: Single testLeapYearBehaviorValidation method\n";
echo "   Benefits: Reduces 3 tests to 1 comprehensive test\n\n";

// Recommendation 2: Year Boundary Tests  
echo "2. YEAR BOUNDARY WEEK PATTERN CONSOLIDATION\n";
echo "   Target: ByWeekNoEdgeCaseTest year boundary methods\n";
echo "   Current: 4 separate methods testing week patterns around year boundaries\n";
echo "   Proposed: Single testYearBoundaryWeekPatterns method\n";
echo "   Benefits: Reduces 4 tests to 1 parameterized test\n\n";

// Recommendation 3: Property Edge Cases
echo "3. PROPERTY EXTRACTION ROBUSTNESS CONSOLIDATION\n";
echo "   Target: PropertyExtractionEdgeCasesTest various edge cases\n";
echo "   Current: 8+ separate methods testing different property edge cases\n";
echo "   Proposed: Single testPropertyExtractionRobustness method\n";
echo "   Benefits: Reduces 8+ tests to 1 comprehensive robustness test\n\n";

echo "=== CONSOLIDATION ANALYSIS COMPLETE ===\n";

function findTestsWithPattern(string $dir, string $pattern): array
{
    $results = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $content = file_get_contents($file->getPathname());
            $matches = [];
            
            // Find all test method names containing the pattern
            preg_match_all(
                '/public function (test.*(?:' . $pattern . ').*?)\s*\(/i',
                $content,
                $matches
            );
            
            if (!empty($matches[1])) {
                $relativePath = str_replace($dir . '/', '', $file->getPathname());
                $results[$relativePath] = $matches[1];
            }
        }
    }

    return $results;
}