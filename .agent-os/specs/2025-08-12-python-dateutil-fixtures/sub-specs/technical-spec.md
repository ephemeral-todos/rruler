# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-12-python-dateutil-fixtures/spec.md

> Created: 2025-08-12
> Version: 1.0.0

## Technical Requirements

### Hybrid Testing Architecture
- Preserve existing `tests/Compatibility/` structure with current test files and methods
- Extend `CompatibilityTestCase` base class to support fixture-based python-dateutil validation
- Implement selective python-dateutil validation for critical test scenarios using pre-generated fixtures
- Maintain backward compatibility with existing test execution and reporting infrastructure

### YAML Fixture System
- Create `tests/fixtures/python-dateutil/input` directory structure for YAML test input definitions
- Create `tests/fixtures/python-dateutil/generated` directory structure for YAML test output definitions
- Implement multi-test input YAML schema supporting: file-level metadata, multiple test cases per file, individual test notes
- Each input file contains: shared metadata (name, description, category) and test_cases array with RRULE, DTSTART, timezone, range, notes
- Develop fixture loader that expands multi-test files into individual test fixtures for PHP test data providers
- Support fixture categories: edge-cases, critical-patterns, regression-tests, performance-benchmarks
- Maintain backward compatibility with legacy single-test format during transition

### Python Integration Layer (Fixture Generation)
- Require Python, python-dateutil, and PyYAML for fixture generation (system-level dependencies)
- Create Python script `scripts/generate-python-dateutil-fixtures.py` that:
  - reads input YAML files from `tests/fixtures/python-dateutil/input/`
  - parses RRULE records and generates occurrences using python-dateutil
  - writes generated YAML files to `tests/fixtures/python-dateutil/generated/` with input data, python-dateutil results, and input file hash (SHA256)
  - only runs when input fixtures change, not during regular test execution

### Fixture-Based Validation Infrastructure
- Extend existing `CompatibilityTestCase` with `assertPythonDateutilFixtureCompatibility()` method
- Implement fixture loading and hash validation to ensure generated fixtures are current
- Implement result comparison logic between Rruler and pre-generated python-dateutil results
- Create hybrid test report showing both sabre/vobject and python-dateutil validation results
- Support selective validation activation via PHPUnit groups or environment variables

## Fixture Generation Workflow

### Developer Workflow
1. **Create/Edit Input YAML** - Define test scenarios in `fixtures/python-dateutil/input/`
2. **Generate Fixtures** - Run `scripts/generate-python-dateutil-fixtures.py`
3. **Generated Output** - Script creates files in `fixtures/python-dateutil/generated/` with:
   - Original input data
   - python-dateutil occurrence results
   - SHA256 hash of input file for validation
   - Script version and python-dateutil version metadata (no timestamps to avoid noisy diffs)

### Test Execution Workflow  
1. **Load Generated Fixtures** - PHP tests read from `fixtures/python-dateutil/generated/` only
2. **Hash Validation** - Compare stored hash with current input file hash
3. **Fixture Validation** - If hash differs, fail with "regenerate fixtures" message  
4. **Result Comparison** - Compare Rruler results against python-dateutil results from fixtures
5. **No Python Execution** - Tests run entirely in PHP using pre-generated fixture data

## Approach

### YAML Schemas

#### Input YAML Schema (Multi-Test Format)

```yaml
metadata:
  name: "Weekly BYDAY Patterns"
  description: "Weekly recurrence on specific weekdays with various termination methods"
  category: "weekly-patterns"
test_cases:
  - rrule: "FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20230115T235959Z"
    dtstart: "2023-01-02T14:00:00"
    timezone: "UTC"
    range:
      start: "2023-01-01T00:00:00"
      end: "2023-01-20T23:59:59"
    notes: "Basic weekly pattern with UNTIL termination"
  - rrule: "FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=5"
    dtstart: "2023-02-28T14:00:00"
    timezone: "UTC"
    notes: "Weekly pattern with COUNT termination"
```

#### Output YAML Schema (Generated)

```yaml
metadata:
  category: weekly-patterns
  description: Weekly recurrence on specific weekdays with various termination methods
  input_hash: bf0c29765ef5b545b2be222c125bdd20d98dbfa6a120f9996243066b37979218
  name: Weekly BYDAY Patterns
  python_dateutil_version: 2.9.0.post0
  script_version: 2.0.0
test_cases:
- expected_occurrences:
  - '2023-01-02T14:00:00+00:00'
  - '2023-01-04T14:00:00+00:00'
  - '2023-01-06T14:00:00+00:00'
  - '2023-01-09T14:00:00+00:00'
  - '2023-01-11T14:00:00+00:00'
  - '2023-01-13T14:00:00+00:00'
  input:
    dtstart: '2023-01-02T14:00:00'
    notes: Basic weekly pattern with UNTIL termination
    range:
      end: '2023-01-20T23:59:59'
      start: '2023-01-01T00:00:00'
    rrule: FREQ=WEEKLY;BYDAY=MO,WE,FR;UNTIL=20230115T235959Z
    timezone: UTC
- expected_occurrences:
  - '2023-02-28T14:00:00+00:00'
  - '2023-03-01T14:00:00+00:00'
  - '2023-03-03T14:00:00+00:00'
  - '2023-03-06T14:00:00+00:00'
  - '2023-03-08T14:00:00+00:00'
  input:
    dtstart: '2023-02-28T14:00:00'
    notes: Weekly pattern with COUNT termination
    rrule: FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=5
    timezone: UTC
```

### Phase 1: Infrastructure Setup
1. Create `scripts/generate-python-dateutil-fixtures.py` for fixture generation
2. Extend CompatibilityTestCase with fixture-based validation capabilities
3. Implement generated YAML fixture loading and parsing infrastructure
   - When reading a generated fixture file, compare the stored hash against the current hash of its related input file
   - If the hash differs, fail the test with message "Input fixtures changed. Run generate-python-dateutil-fixtures.py to regenerate."
4. Create basic test scenarios to validate the fixture-based approach

### Phase 2: Selective Integration
1. Identify critical test scenarios from existing compatibility tests for python-dateutil validation
2. Create input YAML fixtures for edge cases not well-covered by existing sabre/vobject tests
3. Integrate dual validation into selected existing test methods
4. Validate that all existing tests continue to pass with hybrid system

### Phase 3: Comprehensive Coverage
1. Expand YAML fixture coverage for complex RRULE patterns and edge cases
2. Add comprehensive error handling and debugging capabilities
3. Document fixture creation and maintenance workflows

## External Dependencies

### New Python Dependencies (Development)
- **python-dateutil** - Authoritative Python implementation of RFC 5545 RRULE parsing and occurrence generation
- **Justification:** Industry-standard library used by major calendar applications, provides definitive RFC 5545 compliance validation

### New PHP Dependencies (Development)
- **symfony/yaml** - YAML parsing for reading generated fixture files
- **Justification:** Robust YAML handling for fixture loading during test execution, already compatible with existing Symfony ecosystem

### System Dependencies (Development Only)
- **Python 3.8+** - Required for running `generate-python-dateutil-fixtures.py` script
- **PyYAML** - Python YAML library for fixture generation script
- **Justification:** Needed only for fixture generation, not required for regular test execution
