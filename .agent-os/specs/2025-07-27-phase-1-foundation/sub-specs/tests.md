# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-07-27-phase-1-foundation/spec.md

> Created: 2025-07-27
> Version: 1.0.0

## Test Coverage

### Unit Tests

**RruleParser**
- Parse valid FREQ values (DAILY, WEEKLY, MONTHLY, YEARLY)
- Parse INTERVAL parameter with various values
- Parse COUNT parameter with positive integers
- Parse UNTIL parameter with valid datetime formats
- Handle malformed RRULE strings with specific error messages
- Validate parameter combinations and constraints

**Tokenizer**
- Tokenize simple RRULE strings correctly
- Handle whitespace and case variations
- Identify invalid characters and report errors
- Parse parameter=value pairs accurately

**AST Nodes**
- FrequencyNode creation and validation
- IntervalNode with boundary validation (positive integers)
- CountNode with boundary validation (positive integers)
- UntilNode with datetime format validation

**Rrule Value Object**
- Immutable object creation from parsed AST
- Getter methods for all parsed parameters
- String representation for debugging

### Integration Tests

**End-to-End Parsing**
- Parse complete RRULE strings: "FREQ=DAILY;INTERVAL=2;COUNT=10"
- Parse minimal RRULE strings: "FREQ=WEEKLY"
- Parse RRULE with UNTIL: "FREQ=MONTHLY;UNTIL=20241231T235959Z"
- Handle case insensitive input
- Validate parameter order independence

**Error Handling**
- Invalid FREQ values produce specific error messages
- Negative INTERVAL/COUNT values rejected with clear errors
- Malformed UNTIL dates rejected with format guidance
- Unknown parameters handled gracefully

### Mocking Requirements

**No External Dependencies**: Since we're maintaining minimal dependencies, no mocking frameworks needed. All tests use built-in PHP features and test data.

**Test Data Strategy**:
- Predefined RRULE strings for common patterns
- Edge case scenarios for boundary testing
- Invalid input samples for error message validation