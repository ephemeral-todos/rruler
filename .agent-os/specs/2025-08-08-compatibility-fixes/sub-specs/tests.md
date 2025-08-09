# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-08-08-compatibility-fixes/spec.md

> Created: 2025-08-08
> Version: 1.0.0

## Test Coverage

### Regression Tests for Time Portion Fix

- **Yearly Complex Pattern Tests:** Verify time components are preserved in yearly recurrence with BYMONTH, BYDAY combinations
- **Monthly Pattern Time Tests:** Ensure monthly patterns with BYSETPOS maintain original DTSTART time
- **Weekly Pattern Verification:** Confirm weekly patterns continue working correctly with time preservation fixes
- **Edge Case DateTime Tests:** Test patterns crossing time boundaries (midnight, DST transitions) maintain time portions

### Weekly BYSETPOS Boundary Logic Tests

- **RFC 5545 Compliance Tests:** Implement tests based on RFC 5545 interpretation of weekly BYSETPOS boundaries
- **Comparative Behavior Tests:** Document exact differences between Rruler and sabre/dav in test comments
- **Skip Framework Tests:** Tests that fail due to acceptable implementation differences will be marked with skip annotations
- **Documentation Validation Tests:** Ensure COMPATIBILITY_ISSUES.md accurately reflects test skip reasons

### Compatibility Documentation Tests

- **Coverage Verification Tests:** Automated tests to ensure all documented compatibility issues have corresponding test cases
- **Documentation Accuracy Tests:** Validate that documented differences match actual implementation behavior
- **Example Validation Tests:** Verify all examples in COMPATIBILITY_ISSUES.md produce expected outputs

## Mocking Requirements

### sabre/dav Comparison Framework

- **Mock sabre/dav Results:** Use existing comparison framework to validate fixed behaviors
- **Test Data Fixtures:** Maintain test fixtures for patterns that demonstrate fixed vs. acceptable differences
- **Regression Prevention:** Mock original buggy behavior to ensure fixes don't revert

### DateTime Handling Mocks

- **Time Preservation Mocks:** Mock DateTime operations to verify time components are preserved throughout processing
- **Edge Case Simulation:** Mock problematic DateTime scenarios that previously caused time portion loss