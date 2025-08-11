# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-08-11-wkst-support/spec.md

> Created: 2025-08-11
> Status: Complete

## Tasks

- [x] 1. Create WKST AST Node Infrastructure
  - [x] 1.1 Write tests for WkstNode class with valid weekday choices (SU-SA)
  - [x] 1.2 Write tests for WkstNode validation and error handling
  - [x] 1.3 Implement WkstNode class extending NodeWithChoices interface
  - [x] 1.4 Integrate WkstNode into RruleParser tokenizer and AST building
  - [x] 1.5 Verify all WkstNode tests pass

- [x] 2. Extend Rrule Object with WKST Support
  - [x] 2.1 Write tests for Rrule getWeekStart() method and default behavior
  - [x] 2.2 Write tests for WKST integration in RRULE string parsing and output
  - [x] 2.3 Add WKST property and getWeekStart() method to Rrule class
  - [x] 2.4 Update RruleParser to handle WKST parameter in RRULE strings
  - [x] 2.5 Implement RRULE serialization with WKST parameter
  - [x] 2.6 Verify all Rrule WKST tests pass

- [x] 3. Implement Week Calculation Utilities
  - [x] 3.1 Write tests for week boundary calculations with different WKST values
  - [x] 3.2 Write tests for weekday mapping and offset calculations
  - [x] 3.3 Extend DateValidationUtils with WKST-aware week calculation methods
  - [x] 3.4 Implement getWeekBoundaries() method supporting configurable week start
  - [x] 3.5 Create weekday mapping utilities for WKST offset calculations
  - [x] 3.6 Verify all week calculation tests pass

- [x] 4. Update BYDAY Pattern Integration
  - [x] 4.1 Write tests for BYDAY patterns with various WKST configurations
  - [x] 4.2 Write tests for weekly BYDAY occurrences respecting WKST boundaries
  - [x] 4.3 Update ByDayNode occurrence generation to use WKST-aware week calculations
  - [x] 4.4 Modify weekly frequency logic to respect configured week start day
  - [x] 4.5 Ensure BYDAY positional prefixes work correctly with WKST
  - [x] 4.6 Verify all BYDAY WKST integration tests pass

- [x] 5. Implement BYWEEKNO WKST Compatibility
  - [x] 5.1 Write tests for BYWEEKNO calculations with different WKST values
  - [x] 5.2 Write tests for year boundary edge cases with WKST and BYWEEKNO
  - [x] 5.3 Update ByWeekNoNode to adjust week number calculations for WKST
  - [x] 5.4 Implement WKST-aware ISO 8601 week number mapping
  - [x] 5.5 Handle year boundary transitions with non-Monday week starts
  - [x] 5.6 Verify all BYWEEKNO WKST tests pass

- [x] 6. Comprehensive Integration Testing
  - [x] 6.1 Write tests for complex RRULE patterns combining WKST with multiple BY* rules
  - [x] 6.2 Write tests for WKST compatibility with sabre/dav expected results
  - [x] 6.3 Create end-to-end workflow tests for all FREQ types with WKST
  - [x] 6.4 Test edge cases around leap years and year boundaries with WKST
  - [x] 6.5 Validate WKST default behavior (MO) when parameter omitted
  - [x] 6.6 Verify all integration tests pass and maintain 98.7% sabre/dav compatibility