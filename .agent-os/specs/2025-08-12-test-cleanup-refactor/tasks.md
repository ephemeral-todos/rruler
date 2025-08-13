# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-08-12-test-cleanup-refactor/spec.md

> Created: 2025-08-12
> Status: Ready for Implementation

## Tasks

- [x] 1. Infrastructure Test Removal and Benchmark Migration
  - [x] 1.1 Write tests for infrastructure test detection utilities
  - [x] 1.2 Analyze test suite to identify and catalog infrastructure tests
  - [x] 1.3 Remove reflection-based structural tests that validate class architecture
  - [x] 1.4 Remove interface validation tests without behavioral verification
  - [x] 1.5 Move benchmark classes from tests/ to scripts/benchmarks/ directory
  - [x] 1.6 Update benchmark script permissions and rename with proper conventions
  - [x] 1.7 Update documentation references to new benchmark script locations
  - [x] 1.8 Verify all tests pass and no behavioral tests were accidentally removed

- [ ] 2. Testing Utility Organization
  - [ ] 2.1 Write tests for testing utility organization and autoloading
  - [ ] 2.2 Create src/Testing directory structure with appropriate subdirectories
  - [ ] 2.3 Move YamlFixtureLoader from tests/Testing/Fixtures/ to src/Testing/Fixtures/
  - [ ] 2.4 Move test generators and comparators to src/Testing/Utilities/
  - [ ] 2.5 Move fixture loading infrastructure to src/Testing/Fixtures/
  - [ ] 2.6 Update composer.json autoloading configuration for src/Testing
  - [ ] 2.7 Update all test files to reference utilities in new src/Testing locations
  - [ ] 2.8 Verify all tests pass with new utility locations

- [ ] 3. Documentation Test Integration
  - [ ] 3.1 Write tests for documentation example extraction and validation
  - [ ] 3.2 Extract code examples from README.md and identify current patterns
  - [ ] 3.3 Move ReadmeCodeExampleTest.php to tests/Integration/ directory
  - [ ] 3.4 Create integration tests that validate README examples work correctly
  - [ ] 3.5 Implement test workflow ensuring documentation stays current with API
  - [ ] 3.6 Structure tests to validate end-to-end user workflows from documentation
  - [ ] 3.7 Verify all tests pass and documentation examples are properly validated

- [ ] 4. Edge Case Test Consolidation
  - [ ] 4.1 Write tests for edge case consolidation logic and coverage validation
  - [ ] 4.2 Identify overly specific edge case tests that can be consolidated
  - [ ] 4.3 Analyze test coverage to ensure consolidation maintains comprehensive testing
  - [ ] 4.4 Consolidate duplicate test scenarios in occurrence generation tests
  - [ ] 4.5 Combine narrow edge case tests into broader behavioral validation tests
  - [ ] 4.6 Ensure consolidated tests maintain clear failure messages and debugging info
  - [ ] 4.7 Verify all tests pass and coverage percentage is maintained

- [ ] 5. Assertion Pattern Improvement
  - [ ] 5.1 Write tests for assertion pattern analysis and validation utilities
  - [ ] 5.2 Identify string-content assertions that should be behavioral assertions
  - [ ] 5.3 Replace toString() and string comparison assertions with behavior validation
  - [ ] 5.4 Update parser tests to focus on functional outcomes rather than string output
  - [ ] 5.5 Improve error message assertions to validate error types and behaviors
  - [ ] 5.6 Ensure all assertion changes maintain meaningful test failure information
  - [ ] 5.7 Verify all tests pass with improved assertion patterns