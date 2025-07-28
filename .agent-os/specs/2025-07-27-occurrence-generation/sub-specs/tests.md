# Tests Specification

This is the tests coverage details for the spec detailed in @.agent-os/specs/2025-07-27-occurrence-generation/spec.md

> Created: 2025-07-27
> Version: 1.0.0

## Test Coverage

### Unit Tests

**DefaultOccurrenceGenerator**
- Generate daily occurrences with different intervals
- Generate weekly occurrences with different intervals
- Respect COUNT termination conditions
- Respect UNTIL termination conditions
- Generate occurrences within date ranges
- Handle edge cases (COUNT=0, invalid date ranges)
- Timezone handling for DateTime objects
- Generator behavior (lazy evaluation, proper iteration)

**DefaultOccurrenceValidator**
- Validate DateTime instances against daily patterns
- Validate DateTime instances against weekly patterns
- Handle edge cases (start date, boundary conditions)
- Dependency injection with OccurrenceGenerator
- Invalid occurrence detection

### Integration Tests

**End-to-End Occurrence Generation**
- Parse RRULE string, generate occurrences, validate results
- Multiple frequency patterns with real-world examples
- Complex scenarios with COUNT and UNTIL combined with date ranges

**Service Interaction Tests**
- OccurrenceValidator using DefaultOccurrenceGenerator
- Verify generator and validator produce consistent results
- Performance tests for large occurrence sets

### Mocking Requirements

**OccurrenceGenerator Mock**
- Mock for testing OccurrenceValidator in isolation
- Configurable return values for different RRULE patterns
- Test generator behavior without actual date calculations

**DateTimeImmutable Handling**
- No mocking needed - use real DateTimeImmutable objects
- Test with various timezones and edge dates
- Boundary testing around daylight saving time changes

## Test Data Sets

### DAILY Pattern Test Cases
```
- FREQ=DAILY;COUNT=5 (start: 2025-01-01, expect: 5 consecutive days)
- FREQ=DAILY;INTERVAL=2;COUNT=3 (start: 2025-01-01, expect: every other day)
- FREQ=DAILY;UNTIL=20250105T000000Z (start: 2025-01-01, expect: until limit)
- FREQ=DAILY (start: 2025-01-01, range: 2025-01-03 to 2025-01-07)
```

### WEEKLY Pattern Test Cases
```
- FREQ=WEEKLY;COUNT=4 (start: 2025-01-01, expect: 4 weekly occurrences)
- FREQ=WEEKLY;INTERVAL=2;COUNT=3 (start: 2025-01-01, expect: bi-weekly)
- FREQ=WEEKLY;UNTIL=20250201T000000Z (start: 2025-01-01, expect: until limit)
- FREQ=WEEKLY (start: 2025-01-01, range: 2025-01-15 to 2025-02-15)
```

### Edge Cases
```
- COUNT=0 (should generate no occurrences)
- UNTIL date before start date (should generate no occurrences)
- Date range that doesn't include start date
- Start date that equals UNTIL date
- Large COUNT values (performance testing)
```

### Validation Test Cases
```
- Valid occurrences that should return true
- Invalid dates that should return false
- Boundary cases (exactly on UNTIL date, exactly at COUNT limit)
- Timezone mismatches between start and candidate dates
```