# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-08-06-byweekno-support/spec.md

> Created: 2025-08-06
> Status: Ready for Implementation

## Tasks

- [x] 1. Implement ISO 8601 Week Number Utilities
  - [x] 1.1 Write tests for ISO 8601 week number calculations
  - [x] 1.2 Implement getIsoWeekNumber() method in DateValidationUtils
  - [x] 1.3 Implement getFirstDateOfWeek() method for week-to-date conversion
  - [x] 1.4 Implement yearHasWeek53() method for leap week validation
  - [x] 1.5 Add utility methods for week boundary calculations
  - [x] 1.6 Verify all ISO week utility tests pass

- [x] 2. Implement ByWeekNoNode AST Class
  - [x] 2.1 Write tests for ByWeekNoNode parsing and validation
  - [x] 2.2 Create ByWeekNoNode class extending Node interface
  - [x] 2.3 Implement NodeWithChoices interface with week values 1-53
  - [x] 2.4 Add week value parsing with comma-separated validation
  - [x] 2.5 Implement validation for week values 1-53 range
  - [x] 2.6 Add leap week validation during occurrence generation
  - [x] 2.7 Verify all ByWeekNoNode tests pass

- [x] 3. Extend RruleParser for BYWEEKNO Support
  - [x] 3.1 Write tests for BYWEEKNO parameter recognition in parser
  - [x] 3.2 Add BYWEEKNO case to RruleParser parameter parsing
  - [x] 3.3 Integrate ByWeekNoNode instantiation in parser logic
  - [x] 3.4 Verify all parser integration tests pass

- [x] 4. Add BYWEEKNO Support to Rrule Value Object
  - [x] 4.1 Write tests for Rrule getByWeekNo() and hasByWeekNo() methods
  - [x] 4.2 Add private $byWeekNo property to Rrule class
  - [x] 4.3 Implement public getByWeekNo() method returning array
  - [x] 4.4 Implement public hasByWeekNo() method returning boolean
  - [x] 4.5 Update Rrule constructor to accept ByWeekNoNode
  - [x] 4.6 Update toString() method to include BYWEEKNO parameter
  - [x] 4.7 Verify all Rrule integration tests pass

- [x] 5. Implement Occurrence Generation for BYWEEKNO
  - [x] 5.1 Write tests for BYWEEKNO occurrence filtering in yearly patterns
  - [x] 5.2 Add BYWEEKNO filtering logic to DefaultOccurrenceGenerator
  - [x] 5.3 Implement week filtering for yearly frequency patterns
  - [x] 5.4 Handle year boundaries and leap week scenarios
  - [x] 5.5 Ensure integration with existing BYMONTHDAY, BYDAY, and BYMONTH logic
  - [x] 5.6 Verify all occurrence generation tests pass

- [x] 6. Integration Testing and Validation
  - [x] 6.1 Write comprehensive integration tests for BYWEEKNO scenarios
  - [x] 6.2 Test quarterly patterns (BYWEEKNO=13,26,39,52)
  - [x] 6.3 Test bi-annual patterns (BYWEEKNO=1,26)
  - [x] 6.4 Test BYWEEKNO with INTERVAL support
  - [x] 6.5 Test leap week scenarios (week 53)
  - [x] 6.6 Test error handling for invalid week values
  - [x] 6.7 Test year boundary edge cases
  - [x] 6.8 Verify all existing tests still pass with BYWEEKNO integration