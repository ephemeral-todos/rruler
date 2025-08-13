#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Update Documentation Statistics Script
 *
 * This script automatically updates test count statistics in documentation files
 * by running the test suite and extracting actual counts.
 */

// Check for dry-run flag
$dryRun = in_array('--dry-run', $argv, true);

if (!$dryRun) {
    echo "📊 Updating documentation statistics...\n\n";
}

// Get the project root directory
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// Run PHPUnit to get current test statistics
if (!$dryRun) {
    echo "🧪 Running test suite to get current statistics...\n";
}
exec('composer test 2>&1', $output, $exitCode);

if ($exitCode !== 0) {
    if (!$dryRun) {
        echo "❌ Tests failed. Cannot update documentation with failing test counts.\n";
        echo "Please fix failing tests first, then run this script again.\n";
    }
    exit(1);
}

// Parse test output to extract statistics
$testOutput = implode("\n", $output);
$testCount = 0;
$assertionCount = 0;

// Extract test and assertion counts from PHPUnit output
if (preg_match('/Tests: (\d+), Assertions: (\d+)/', $testOutput, $matches)) {
    $testCount = (int) $matches[1];
    $assertionCount = (int) $matches[2];
} elseif (preg_match('/OK \([^)]*(\d+) tests?, (\d+) assertions?\)/', $testOutput, $matches)) {
    $testCount = (int) $matches[1];
    $assertionCount = (int) $matches[2];
}

if ($testCount === 0) {
    if (!$dryRun) {
        echo "❌ Could not extract test statistics from PHPUnit output.\n";
        echo "PHPUnit output:\n" . $testOutput . "\n";
    }
    exit(1);
}

echo "Found {$testCount} tests with {$assertionCount} assertions\n";

// If dry-run, just output the statistics and exit
if ($dryRun) {
    exit(0);
}

// Files to update with their update patterns
$filesToUpdate = [
    'README.md' => [
        // Update test count references
        '/- 🧪 \*\*Comprehensive testing\*\* - \d+[\d,]*\+ tests with/' => "- 🧪 **Comprehensive testing** - {$testCount}+ tests with",
        '/Our comprehensive test suite with \d+[\d,]*\+ tests validates:/' => "Our comprehensive test suite with {$testCount}+ tests validates:",
        '/- \*\*Compatible occurrence generation\*\* for all main RRULE patterns \(\d+[\d,]* tests passing with \d+[\d,]*\+ assertions\)/' => "- **Compatible occurrence generation** for all main RRULE patterns ({$testCount} tests passing with {$assertionCount}+ assertions)",
    ],
    'COMPATIBILITY_ISSUES.md' => [
        '/- \*\*Total Tests\*\*: \d+[\d,]* comprehensive tests/' => "- **Total Tests**: {$testCount} comprehensive tests",
        '/- \*\*Main Test Suite\*\*: \d+[\d,]* tests passing/' => "- **Main Test Suite**: {$testCount} tests passing",
        '/- \*\*Total Assertions\*\*: \d+[\d,]*\+ individual validations/' => "- **Total Assertions**: {$assertionCount}+ individual validations",
        '/## Testing Statistics\s*\n\s*- \*\*Total Tests\*\*: \d+[\d,]* comprehensive test cases/' => "## Testing Statistics\n\n- **Total Tests**: {$testCount} comprehensive test cases",
        '/- \*\*Main Test Suite\*\*: \d+[\d,]* tests passing \(100% success rate\)/' => "- **Main Test Suite**: {$testCount} tests passing (100% success rate)",
        '/- \*\*Comprehensive Coverage\*\*: \d+[\d,]* tests covering/' => "- **Comprehensive Coverage**: {$testCount} tests covering",
    ],
    '.agent-os/product/roadmap.md' => [
        '/- \*\*\d+[\d,]* Test Suite\*\* - Unit, integration/' => "- **{$testCount} Test Suite** - Unit, integration",
        '/All \d+[\d,]* tests passing with \d+[\d,]* assertions/' => "All {$testCount} tests passing with {$assertionCount} assertions",
    ],
];

$updatedFiles = [];

foreach ($filesToUpdate as $filename => $patterns) {
    $filepath = $projectRoot . '/' . $filename;
    
    if (!file_exists($filepath)) {
        echo "⚠️  File not found: {$filename}, skipping\n";
        continue;
    }

    $content = file_get_contents($filepath);
    $originalContent = $content;
    
    foreach ($patterns as $pattern => $replacement) {
        $newContent = preg_replace($pattern, $replacement, $content);
        if ($newContent !== null) {
            $content = $newContent;
        }
    }
    
    if ($content !== $originalContent) {
        file_put_contents($filepath, $content);
        $updatedFiles[] = $filename;
        echo "✅ Updated {$filename}\n";
    } else {
        echo "ℹ️  No changes needed in {$filename}\n";
    }
}

if (!$dryRun) {
    echo "\n";

    if (empty($updatedFiles)) {
        echo "🎉 All documentation files are already up to date!\n";
    } else {
        echo "🎉 Successfully updated " . count($updatedFiles) . " documentation file(s):\n";
        foreach ($updatedFiles as $file) {
            echo "   - {$file}\n";
        }
        echo "\nRun the documentation-consistency tests to verify updates:\n";
        echo "   composer test -- --group=documentation-consistency\n";
    }

    echo "\n📊 Documentation statistics update complete!\n";
}