# Spec Tasks

## Tasks

- [ ] 1. Create ByMonthDayNode AST Node Class
  - [ ] 1.1 Write tests for ByMonthDayNode parsing and validation
  - [ ] 1.2 Create ByMonthDayNode class implementing NodeWithChoices interface
  - [ ] 1.3 Implement parse() method for comma-separated positive and negative day values
  - [ ] 1.4 Add validation for day value ranges (1-31, -1 to -31) and rejection of zero
  - [ ] 1.5 Implement getChoices() method returning valid day value range
  - [ ] 1.6 Verify all ByMonthDayNode tests pass

- [ ] 2. Integrate BYMONTHDAY into RruleParser
  - [ ] 2.1 Write tests for RRULE parsing with BYMONTHDAY parameter
  - [ ] 2.2 Add BYMONTHDAY to NODE_MAP in RruleParser
  - [ ] 2.3 Update parser to handle BYMONTHDAY parameter recognition
  - [ ] 2.4 Ensure tokenizer correctly processes BYMONTHDAY format
  - [ ] 2.5 Verify parser creates correct ByMonthDayNode instances from RRULE strings
  - [ ] 2.6 Verify all parser integration tests pass

- [ ] 3. Extend Rrule Object with BYMONTHDAY Support
  - [ ] 3.1 Write tests for Rrule object BYMONTHDAY property access
  - [ ] 3.2 Add byMonthDay property to Rrule class as private readonly array
  - [ ] 3.3 Implement getByMonthDay() method returning array of day values
  - [ ] 3.4 Update Rrule constructor to accept ByMonthDayNode parameter
  - [ ] 3.5 Maintain immutability pattern while supporting new property
  - [ ] 3.6 Verify all Rrule object tests pass

- [ ] 4. Implement Month Length and Date Validation Logic
  - [ ] 4.1 Write tests for month length calculation including leap year handling
  - [ ] 4.2 Create helper method for calculating days in month considering leap years
  - [ ] 4.3 Implement negative day value resolution to positive day numbers
  - [ ] 4.4 Add date validation logic to skip invalid day/month combinations
  - [ ] 4.5 Create comprehensive test cases for edge cases (Feb 29, April 31, etc.)
  - [ ] 4.6 Verify all date validation tests pass

- [ ] 5. Enhance OccurrenceGenerator for BYMONTHDAY Filtering
  - [ ] 5.1 Write tests for occurrence generation with BYMONTHDAY patterns
  - [ ] 5.2 Extend DefaultOccurrenceGenerator to apply BYMONTHDAY filtering
  - [ ] 5.3 Implement BYMONTHDAY logic for FREQ=MONTHLY patterns
  - [ ] 5.4 Implement BYMONTHDAY logic for FREQ=YEARLY patterns
  - [ ] 5.5 Integrate month length validation during occurrence generation
  - [ ] 5.6 Handle empty result sets gracefully when no valid dates exist
  - [ ] 5.7 Verify all occurrence generation tests pass

- [ ] 6. Create Comprehensive Integration Tests
  - [ ] 6.1 Write integration tests for complete BYMONTHDAY workflows
  - [ ] 6.2 Test combinations of BYMONTHDAY with existing parameters (COUNT, UNTIL, INTERVAL)
  - [ ] 6.3 Create test cases for multiple BYMONTHDAY values (comma-separated lists)
  - [ ] 6.4 Test negative BYMONTHDAY values across different months and leap years
  - [ ] 6.5 Verify error handling for invalid BYMONTHDAY parameter combinations
  - [ ] 6.6 Verify all integration tests pass