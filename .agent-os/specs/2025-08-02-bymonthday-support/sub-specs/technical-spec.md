# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-02-bymonthday-support/spec.md

## Technical Requirements

### AST Node Implementation
- **ByMonthDayNode Class** - Create new AST node class implementing `NodeWithChoices` interface for BYMONTHDAY parameter parsing
- **Value Parsing** - Parse comma-separated integer values from RRULE strings, supporting both positive (1-31) and negative (-1 to -31) values
- **Validation Logic** - Implement validation for day value ranges and ensure negative values are properly handled

### Parser Integration
- **RruleParser Enhancement** - Extend existing parser to recognize BYMONTHDAY parameter and create ByMonthDayNode instances
- **NODE_MAP Extension** - Add BYMONTHDAY mapping to the parser's NODE_MAP for automatic node creation
- **Tokenizer Support** - Ensure tokenizer correctly handles BYMONTHDAY parameter format with comma-separated values

### Rrule Object Integration
- **Property Addition** - Add `byMonthDay` property to Rrule class to store parsed month day values as integer array
- **Getter Method** - Implement `getByMonthDay(): array` method to retrieve month day specifications
- **Immutability** - Maintain immutable pattern of Rrule objects while supporting new property

### Occurrence Generation Logic
- **OccurrenceGenerator Enhancement** - Extend DefaultOccurrenceGenerator to apply BYMONTHDAY filtering during occurrence calculation
- **Month Length Calculation** - Implement proper month length calculation considering leap years for February (28/29 days)
- **Negative Value Resolution** - Convert negative BYMONTHDAY values to positive day numbers based on actual month length
- **Date Validation** - Skip invalid dates (e.g., February 30th, April 31st) when generating occurrences
- **FREQ Integration** - Apply BYMONTHDAY filtering appropriately for FREQ=MONTHLY and FREQ=YEARLY patterns

### Edge Case Handling
- **Leap Year Support** - Correctly handle February 29th in leap years and February 28th in non-leap years
- **Month Boundary Validation** - Validate that positive day values don't exceed actual month length (28-31 days)
- **Invalid Date Skipping** - Gracefully skip invalid date combinations without throwing exceptions
- **Empty Result Sets** - Handle cases where BYMONTHDAY values don't produce any valid dates for certain months

### Error Handling and Validation
- **Parameter Validation** - Validate BYMONTHDAY values are within acceptable ranges (1-31, -1 to -31)
- **Zero Value Rejection** - Reject invalid day value of 0 as per RFC 5545 specification
- **Frequency Compatibility** - Ensure BYMONTHDAY is only used with compatible FREQ values (MONTHLY, YEARLY)
- **Clear Error Messages** - Provide descriptive error messages for invalid BYMONTHDAY parameter combinations

### Performance Considerations
- **Efficient Date Calculation** - Use optimized algorithms for month length and leap year calculations
- **Memory Usage** - Store month day values efficiently without unnecessary object creation
- **Generator Patterns** - Maintain lazy evaluation approach for large occurrence sets with BYMONTHDAY filtering
- **Caching Considerations** - Consider caching month length calculations for repeated date generation