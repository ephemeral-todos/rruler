# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-06-byweekno-support/spec.md

> Created: 2025-08-06
> Version: 1.0.0

## Technical Requirements

- **ByWeekNoNode Class**: Implement AST node extending base Node interface with getChoices() returning week numbers 1-53
- **Week Value Parsing**: Accept comma-separated integers (e.g., "1,13,26,39,52") with validation for range 1-53
- **Rrule Integration**: Add private $byWeekNo property and public getByWeekNo() method returning array of integers
- **RruleParser Extension**: Recognize BYWEEKNO parameter and instantiate ByWeekNoNode during AST parsing
- **ISO 8601 Week Logic**: Implement week number calculations following ISO 8601 standard (Monday as first day, week 1 contains January 4th)
- **Occurrence Generation**: Filter yearly pattern occurrences by ISO week number, preserving day-of-week and time from DTSTART
- **Leap Week Handling**: Support years with 53 weeks and validate week 53 exists in target years
- **Validation Logic**: Throw InvalidArgumentException for values outside 1-53 range with descriptive error messages
- **Test Coverage**: Unit tests for ByWeekNoNode parsing, ISO week calculations, and occurrence generation scenarios
- **Integration Testing**: Validate against existing BYMONTHDAY, BYDAY, and BYMONTH combinations work correctly

## Approach

### AST Node Implementation

Create `ByWeekNoNode` class in `src/Parser/Ast/` following the established pattern:

- Extend the base `Node` class
- Implement `NodeWithChoices` interface
- Parse comma-separated week values (1-53)
- Validate each week value is within valid range
- Store parsed values as array of integers

### ISO 8601 Week Number Logic

Implement accurate week number calculations:

- Week 1 is the first week of the year that contains at least 4 days of January
- Monday is considered the first day of the week
- Years can have 52 or 53 weeks (leap weeks)
- Week 53 only exists in years where January 1st is Thursday or leap years where January 1st is Wednesday

### Parser Integration

Extend `RruleParser` to recognize the `BYWEEKNO` parameter:

- Add `BYWEEKNO` case to parameter parsing switch statement
- Instantiate `ByWeekNoNode` with parameter value
- Integrate into `Rrule` object construction

### Rrule Object Extension

Add BYWEEKNO support to the `Rrule` value object:

- Add private `$byWeekNo` property (array of integers)
- Add public `getByWeekNo()` method
- Add public `hasByWeekNo()` method
- Update constructor to accept `ByWeekNoNode`
- Maintain immutability of the object

### Occurrence Generation Logic

Implement week filtering in `DefaultOccurrenceGenerator`:

- Apply BYWEEKNO filtering specifically for YEARLY frequency patterns
- Convert DTSTART to ISO week number and filter candidates by week number
- Preserve existing BYMONTHDAY, BYDAY, and BYMONTH filtering logic
- Handle year boundaries and leap weeks correctly
- Maintain proper date/time preservation from DTSTART

### Week Number Utilities

Create utility methods for ISO 8601 calculations:

- `getIsoWeekNumber(DateTimeImmutable $date): int` - Get ISO week number for a date
- `getFirstDateOfWeek(int $year, int $weekNumber): DateTimeImmutable` - Get first date of specified week
- `yearHasWeek53(int $year): bool` - Check if year has 53 weeks
- Integration with existing `DateValidationUtils` class

### Error Handling

Implement comprehensive validation:

- Week values must be integers 1-53
- Validate week 53 only exists in appropriate years during occurrence generation
- Provide clear error messages for invalid values
- Handle edge cases like empty BYWEEKNO values
- Maintain consistency with existing validation patterns

## External Dependencies

No new external dependencies required. Implementation uses existing PHP DateTime classes and follows established patterns in the codebase. ISO 8601 calculations will be implemented using built-in PHP date functions.