# Fixture Generation Workflow

> Last Updated: 2025-08-12
> Version: 2.0.0

This document provides comprehensive guidance for managing the python-dateutil fixture generation system used for RFC 5545 RRULE compatibility validation in Rruler.

## Overview

The fixture generation system creates reference test data by running RRULE patterns through python-dateutil (the gold standard for RFC 5545 implementation) and storing the results for validation against Rruler's implementation. This provides dual compatibility validation alongside existing sabre/vobject testing.

## Architecture

### Directory Structure
```
tests/fixtures/python-dateutil/
├── input/                    # Input YAML specifications
│   ├── basic_daily.yaml     # Basic daily patterns
│   ├── weekly_byday.yaml    # Weekly BYDAY patterns  
│   ├── monthly_bysetpos.yaml # Monthly BYSETPOS patterns
│   └── ...                  # Additional pattern files
└── generated/               # Generated fixture files
    ├── basic_daily.yaml     # Generated from input/basic_daily.yaml
    ├── weekly_byday.yaml    # Generated from input/weekly_byday.yaml
    └── ...                  # Corresponding generated files
```

### Core Components

1. **Python Generator Script** (`scripts/generate-python-dateutil-fixtures.py`)
   - Reads input YAML specifications
   - Generates occurrences using python-dateutil
   - Creates output YAML with expected results
   - Calculates integrity hashes

2. **PHP Fixture Loader** (`src/Testing/Fixtures/YamlFixtureLoader.php`)
   - Loads and validates fixture files
   - Provides caching for performance
   - Converts fixtures to PHPUnit data providers

3. **PHP Test Integration** (`tests/Compatibility/CompatibilityTestCase.php`)
   - Extends base test case with fixture validation
   - Provides `assertPythonDateutilFixtureCompatibility()` method
   - Handles batch loading and performance optimization

## Input Fixture Format

### Multi-Test Format (Recommended)

```yaml
metadata:
  name: "Pattern Collection Name"
  description: "Detailed description of what this collection tests"
  category: "basic-patterns|edge-cases|critical-patterns|regression-tests"

test_cases:
  - rrule: "FREQ=DAILY;COUNT=5"
    dtstart: "2023-01-01T09:00:00"
    timezone: "UTC"
    title: "Basic daily pattern with COUNT termination"
    notes: "Tests simple daily recurrence with fixed count"
    range:
      start: "2023-01-01T00:00:00"
      end: "2023-01-10T23:59:59"
  
  - rrule: "FREQ=DAILY;INTERVAL=2;UNTIL=20230110T090000Z"
    dtstart: "2023-01-01T09:00:00"
    timezone: "UTC"
    title: "Daily pattern with interval and UNTIL termination"
    notes: "Tests daily every 2 days until specific date"
```

### Single-Test Format (Legacy Support)

```yaml
metadata:
  name: "Single Pattern Test"
  description: "Description of single test case"
  category: "basic-patterns"

input:
  name: "Test case name"
  rrule: "FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6"
  dtstart: "2023-01-02T10:00:00"
  timezone: "UTC"
  range:
    start: "2023-01-01T00:00:00"
    end: "2023-01-31T23:59:59"

expected_occurrences:
  - "2023-01-02T10:00:00+00:00"
  - "2023-01-04T10:00:00+00:00"
  # ... additional occurrences
```

### Field Definitions

- **metadata.name**: Human-readable name for the fixture collection
- **metadata.description**: Detailed description of what the fixture tests
- **metadata.category**: Categorization for filtering (`basic-patterns`, `edge-cases`, `critical-patterns`, `regression-tests`)
- **test_cases[].rrule**: RFC 5545 RRULE string to test
- **test_cases[].dtstart**: Start date/time in ISO format
- **test_cases[].timezone**: Timezone identifier (default: "UTC")
- **test_cases[].title**: Brief behavioral description of the test case
- **test_cases[].notes**: Detailed description of what the RRULE pattern represents
- **test_cases[].range**: Optional date range for occurrence generation

## Generator Script Usage

### Basic Usage

```bash
# Generate all fixtures (default directories)
python scripts/generate-python-dateutil-fixtures.py

# Specify custom directories
python scripts/generate-python-dateutil-fixtures.py input_dir output_dir

# Generate specific fixture files
python scripts/generate-python-dateutil-fixtures.py \
    tests/fixtures/python-dateutil/input/basic_daily.yaml \
    tests/fixtures/python-dateutil/generated/basic_daily.yaml
```

### Command Line Options

```bash
python scripts/generate-python-dateutil-fixtures.py --help

Options:
  -h, --help            Show help message
  -v, --verbose         Enable verbose output
  --input-dir DIR       Input directory (default: tests/fixtures/python-dateutil/input/)
  --output-dir DIR      Output directory (default: tests/fixtures/python-dateutil/generated/)
  --max-occurrences N   Maximum occurrences to generate per test case (default: 50)
```

### Error Handling

The generator script provides comprehensive error handling:

- **Invalid RRULE**: Clear error message with problematic pattern
- **Parse Errors**: Detailed location information for YAML/date parsing issues
- **File I/O Errors**: Helpful messages for permission and path issues
- **Validation Errors**: Hash mismatches and integrity check failures

## Output Fixture Format

Generated fixtures include:

```yaml
metadata:
  input_hash: "sha256-hash-of-input-file"
  python_dateutil_version: "2.8.2"
  script_version: "2.0.0"
  generated_date: "2023-01-15T10:30:00Z"
  name: "Pattern Collection Name"
  category: "basic-patterns"

input:
  rrule: "FREQ=DAILY;COUNT=5"
  dtstart: "2023-01-01T09:00:00"
  timezone: "UTC"
  range:
    start: "2023-01-01T00:00:00"
    end: "2023-01-10T23:59:59"

expected_occurrences:
  - "2023-01-01T09:00:00+00:00"
  - "2023-01-02T09:00:00+00:00"
  - "2023-01-03T09:00:00+00:00"
  - "2023-01-04T09:00:00+00:00"
  - "2023-01-05T09:00:00+00:00"
```

## PHP Integration

### Using Fixture Validation in Tests

```php
use EphemeralTodos\Rruler\Tests\Compatibility\CompatibilityTestCase;

class MyCompatibilityTest extends CompatibilityTestCase
{
    public function testBasicDailyPattern(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'basic_daily',
            'Testing basic daily pattern compatibility'
        );
    }
    
    public function testWithGroups(): void
    {
        $this->assertPythonDateutilFixtureCompatibility(
            'complex_pattern',
            'Testing complex pattern',
            ['python-dateutil-validation'] // PHPUnit groups
        );
    }
}
```

### Batch Testing with Data Providers

```php
/**
 * @dataProvider basicPatternsProvider
 */
public function testBasicPatterns(string $fixtureName, string $description): void
{
    $this->assertPythonDateutilFixtureCompatibility($fixtureName, $description);
}

public static function basicPatternsProvider(): array
{
    $loader = new YamlFixtureLoader(__DIR__ . '/../fixtures/python-dateutil/generated');
    return $loader->createPythonDateutilDataProvider('basic-patterns');
}
```

### Performance Optimization

The fixture system includes several performance optimizations:

1. **Static Caching**: Fixtures are cached in memory for test session duration
2. **Batch Loading**: `getPreloadedFixtures()` loads all fixtures once
3. **File Modification Tracking**: Cache invalidation based on file timestamps
4. **Lazy Loading**: Fixtures loaded only when needed

## Workflow Procedures

### Adding New Test Patterns

1. **Create Input Fixture**:
   ```bash
   # Create new input file
   touch tests/fixtures/python-dateutil/input/new_pattern.yaml
   ```

2. **Define Test Cases**:
   ```yaml
   metadata:
     name: "New Pattern Collection"
     description: "Description of new patterns being tested"
     category: "basic-patterns"
   
   test_cases:
     - rrule: "YOUR_RRULE_HERE"
       dtstart: "2023-01-01T09:00:00"
       timezone: "UTC"
       title: "Brief description"
       notes: "Detailed explanation of pattern"
   ```

3. **Generate Fixtures**:
   ```bash
   python scripts/generate-python-dateutil-fixtures.py
   ```

4. **Create PHP Tests**:
   ```php
   public function testNewPattern(): void
   {
       $this->assertPythonDateutilFixtureCompatibility(
           'new_pattern',
           'Testing new pattern compatibility'
       );
   }
   ```

5. **Verify Results**:
   ```bash
   composer test
   ```

### Updating Existing Fixtures

1. **Modify Input Files**: Update YAML specifications as needed
2. **Regenerate**: Run generator script to update expected results
3. **Validate Changes**: Ensure tests still pass or update as appropriate
4. **Commit Changes**: Include both input and generated files in commits

### Debugging Fixture Issues

1. **Hash Mismatches**:
   ```bash
   # Regenerate to fix integrity issues
   python scripts/generate-python-dateutil-fixtures.py
   ```

2. **Occurrence Mismatches**:
   ```bash
   # Run specific test with detailed output
   phpunit --filter testSpecificPattern --verbose
   ```

3. **Performance Issues**:
   ```bash
   # Run performance tests
   phpunit tests/Compatibility/Performance/FixtureValidationPerformanceTest.php
   ```

## Maintenance Procedures

### Regular Maintenance

**Monthly Tasks**:
- [ ] Regenerate all fixtures to ensure consistency
- [ ] Run full test suite including performance tests
- [ ] Review fixture categories and organization
- [ ] Update python-dateutil version if needed

**Before Releases**:
- [ ] Comprehensive fixture validation
- [ ] Performance regression testing
- [ ] Verify all fixture integrity hashes
- [ ] Update documentation if patterns changed

### Version Updates

**When updating python-dateutil**:

1. **Update Environment**:
   ```bash
   pip install --upgrade python-dateutil
   ```

2. **Regenerate All Fixtures**:
   ```bash
   python scripts/generate-python-dateutil-fixtures.py
   ```

3. **Validate Changes**:
   ```bash
   composer test
   git diff tests/fixtures/python-dateutil/generated/
   ```

4. **Update Documentation**: Note version changes in fixture metadata

### Troubleshooting

#### Common Issues

**File Not Found Errors**:
- Verify input directory structure
- Check file permissions
- Ensure consistent naming between input and test references

**RRULE Parse Errors**:
- Validate RRULE syntax against RFC 5545
- Check for unsupported parameters in python-dateutil
- Verify timezone and date format correctness

**Performance Degradation**:
- Clear fixture cache: `YamlFixtureLoader::clearCache()`
- Check file modification timestamps
- Review batch loading efficiency

**Integrity Hash Failures**:
- Regenerate fixtures to recalculate hashes
- Verify input files haven't been corrupted
- Check for encoding issues in YAML files

#### Diagnostic Commands

```bash
# Validate all fixtures
find tests/fixtures/python-dateutil/generated/ -name "*.yaml" -exec \
    python -c "import yaml; yaml.safe_load(open('{}'))" \;

# Check fixture integrity
composer test tests/Unit/Testing/Fixtures/YamlFixtureLoaderTest.php

# Performance benchmarking
composer test tests/Compatibility/Performance/FixtureValidationPerformanceTest.php
```

## Best Practices

### Input File Organization

1. **Logical Grouping**: Group related patterns in same file
2. **Clear Naming**: Use descriptive file names reflecting pattern types
3. **Category Classification**: Use consistent category naming
4. **Documentation**: Include comprehensive descriptions and notes

### Test Case Design

1. **Representative Patterns**: Cover common real-world usage
2. **Edge Cases**: Include boundary conditions and error cases
3. **Performance Consideration**: Limit occurrence counts for large patterns
4. **Clear Documentation**: Use descriptive titles and notes

### Maintenance Strategy

1. **Version Control**: Track both input and generated files
2. **Regular Updates**: Keep fixtures synchronized with code changes
3. **Performance Monitoring**: Track validation performance over time
4. **Documentation**: Keep workflow documentation current

## Integration with CI/CD

### GitHub Actions Integration

```yaml
name: Fixture Validation
on: [push, pull_request]

jobs:
  fixture-validation:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Setup Python
        uses: actions/setup-python@v4
        with:
          python-version: '3.11'
      - name: Install python-dateutil
        run: pip install python-dateutil PyYAML
      - name: Regenerate fixtures
        run: python scripts/generate-python-dateutil-fixtures.py
      - name: Check for changes
        run: git diff --exit-code tests/fixtures/python-dateutil/generated/
      - name: Run fixture tests
        run: composer test
```

### Performance Monitoring

Set up performance benchmarks to track fixture validation performance:

- **Individual Fixture Target**: <100ms per fixture validation
- **Batch Operation Target**: <500ms for batch operations
- **Memory Usage**: Monitor fixture cache memory consumption
- **Cache Effectiveness**: Track cache hit rates

## Conclusion

The fixture generation workflow provides a robust foundation for python-dateutil compatibility validation. By following these procedures, you can maintain high-quality RFC 5545 compliance testing with comprehensive reference validation against the industry-standard python-dateutil implementation.

**Key Benefits**:
- **Dual Validation**: Both sabre/vobject and python-dateutil compatibility
- **Automated Generation**: Consistent, reproducible fixture generation
- **Performance Optimized**: Caching and batch loading for fast test execution
- **Comprehensive Coverage**: Systematic testing of RRULE patterns
- **Maintainable**: Clear workflow procedures and troubleshooting guides

For questions or issues with the fixture generation system, refer to the troubleshooting section or examine the test implementation in `tests/Compatibility/PythonDateutilFixtureCompatibilityTest.php`.