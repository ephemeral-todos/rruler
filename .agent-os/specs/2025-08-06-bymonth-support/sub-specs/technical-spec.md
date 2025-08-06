# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-06-bymonth-support/spec.md

> Created: 2025-08-06
> Version: 1.0.0

## Technical Requirements

- **ByMonthNode Class**: Implement AST node extending base Node interface with getChoices() returning [1,2,3,4,5,6,7,8,9,10,11,12]
- **Month Value Parsing**: Accept comma-separated integers (e.g., "3,6,9,12") with validation for range 1-12
- **Rrule Integration**: Add private $byMonth property and public getByMonth() method returning array of integers
- **RruleParser Extension**: Recognize BYMONTH parameter and instantiate ByMonthNode during AST parsing
- **Occurrence Generation**: Filter yearly pattern occurrences by month, preserving day-of-month and time from DTSTART
- **Validation Logic**: Throw InvalidArgumentException for values outside 1-12 range with descriptive error messages
- **Test Coverage**: Unit tests for ByMonthNode parsing, Rrule integration, and occurrence generation scenarios
- **Integration Testing**: Validate against existing BYMONTHDAY and BYDAY combinations work correctly

## Approach

### AST Node Implementation

Create `ByMonthNode` class in `src/Parser/Ast/` following the established pattern of other BY* parameter nodes. The node will:

- Extend the base `Node` class
- Implement `NodeWithChoices` interface
- Parse comma-separated month values (1-12)
- Validate each month value is within valid range
- Store parsed values as array of integers

### Parser Integration

Extend `RruleParser` to recognize the `BYMONTH` parameter during tokenization and AST building:

- Add `BYMONTH` case to parameter parsing switch statement
- Instantiate `ByMonthNode` with parameter value
- Integrate into `Rrule` object construction

### Rrule Object Extension

Add BYMONTH support to the `Rrule` value object:

- Add private `$byMonth` property (array of integers)
- Add public `getByMonth()` method
- Update constructor to accept `ByMonthNode`
- Maintain immutability of the object

### Occurrence Generation Logic

Implement month filtering in `DefaultOccurrenceGenerator`:

- Apply BYMONTH filtering specifically for YEARLY frequency patterns
- Filter candidate dates by checking if month is in BYMONTH array
- Preserve existing BYMONTHDAY and BYDAY filtering logic
- Maintain proper date/time preservation from DTSTART

### Error Handling

Implement comprehensive validation:

- Month values must be integers 1-12
- Provide clear error messages for invalid values
- Handle edge cases like empty BYMONTH values
- Maintain consistency with existing validation patterns

## External Dependencies

No new external dependencies required. Implementation uses existing PHP DateTime classes and follows established patterns in the codebase.