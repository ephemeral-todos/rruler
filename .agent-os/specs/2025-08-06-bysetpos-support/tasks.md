# Spec Tasks

These are the tasks to be completed for the spec detailed in @.agent-os/specs/2025-08-06-bysetpos-support/spec.md

> Created: 2025-08-06
> Status: **COMPLETED** ✅
> Completed: 2025-08-06

## Tasks

- [x] 1. Implement BYSETPOS Parser and AST Node ✅
  - [x] 1.1 Write tests for BySetPosNode parsing and validation
  - [x] 1.2 Create BySetPosNode class extending Node interface
  - [x] 1.3 Implement NodeWithChoices interface for position values
  - [x] 1.4 Add position value parsing with comma-separated validation
  - [x] 1.5 Implement validation for non-zero integers and reasonable bounds
  - [x] 1.6 Add BYSETPOS case to RruleParser parameter parsing
  - [x] 1.7 Verify all BySetPosNode and parser tests pass

- [x] 2. Extend Rrule Value Object for BYSETPOS
  - [x] 2.1 Write tests for Rrule getBySetPos() and hasBySetPos() methods
  - [x] 2.2 Add private $bySetPos property to Rrule class
  - [x] 2.3 Implement public getBySetPos() method returning array
  - [x] 2.4 Implement public hasBySetPos() method returning boolean
  - [x] 2.5 Update Rrule constructor to accept BySetPosNode
  - [x] 2.6 Update toString() method to include BYSETPOS parameter
  - [x] 2.7 Verify all Rrule integration tests pass

- [x] 3. Implement BYSETPOS Occurrence Selection Logic
  - [x] 3.1 Write tests for BYSETPOS occurrence expansion and selection
  - [x] 3.2 Add BYSETPOS filtering logic to DefaultOccurrenceGenerator
  - [x] 3.3 Implement two-phase generation: expand then select by position
  - [x] 3.4 Support positive indexing (1, 2, 3...) from beginning of set
  - [x] 3.5 Support negative indexing (-1, -2, -3...) from end of set
  - [x] 3.6 Handle edge cases when BYSETPOS position exceeds available occurrences
  - [x] 3.7 Ensure integration with existing BYDAY, BYMONTHDAY, BYMONTH, BYWEEKNO logic
  - [x] 3.8 Verify all occurrence generation tests pass

- [x] 4. BYSETPOS Validation and Error Handling
  - [x] 4.1 Write tests for BYSETPOS validation scenarios
  - [x] 4.2 Implement validation that BYSETPOS requires other BY* rules
  - [x] 4.3 Add clear error messages for invalid BYSETPOS usage
  - [x] 4.4 Validate position values are within reasonable bounds
  - [x] 4.5 Test error handling for malformed BYSETPOS values
  - [x] 4.6 Verify all validation tests pass

- [x] 5. Integration Testing and Real-World Patterns
  - [x] 5.1 Write comprehensive integration tests for BYSETPOS scenarios
  - [x] 5.2 Test "last Sunday of March" pattern (FREQ=YEARLY;BYMONTH=3;BYDAY=SU;BYSETPOS=-1)
  - [x] 5.3 Test "first and last weekday" pattern (FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=1,-1)
  - [x] 5.4 Test quarterly patterns with BYSETPOS selection
  - [x] 5.5 Test BYSETPOS with BYWEEKNO combinations
  - [x] 5.6 Test multiple position selection (BYSETPOS=1,2,-2,-1)
  - [x] 5.7 Verify all existing tests still pass with BYSETPOS integration
  - [x] 5.8 Performance validation for complex BYSETPOS patterns