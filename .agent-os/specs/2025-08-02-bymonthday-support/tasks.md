# Spec Tasks

## Tasks

- [x] 1. Create ByMonthDayNode AST Node Class
  - [x] 1.1 Write tests for ByMonthDayNode parsing and validation
  - [x] 1.2 Create ByMonthDayNode class implementing NodeWithChoices interface
  - [x] 1.3 Implement parse() method for comma-separated positive and negative day values
  - [x] 1.4 Add validation for day value ranges (1-31, -1 to -31) and rejection of zero
  - [x] 1.5 Implement getChoices() method returning valid day value range
  - [x] 1.6 Verify all ByMonthDayNode tests pass

- [x] 2. Integrate BYMONTHDAY into RruleParser
  - [x] 2.1 Write tests for RRULE parsing with BYMONTHDAY parameter
  - [x] 2.2 Add BYMONTHDAY to NODE_MAP in RruleParser
  - [x] 2.3 Update parser to handle BYMONTHDAY parameter recognition
  - [x] 2.4 Ensure tokenizer correctly processes BYMONTHDAY format
  - [x] 2.5 Verify parser creates correct ByMonthDayNode instances from RRULE strings
  - [x] 2.6 Verify all parser integration tests pass

- [x] 3. Extend Rrule Object with BYMONTHDAY Support
  - [x] 3.1 Write tests for Rrule object BYMONTHDAY property access
  - [x] 3.2 Add byMonthDay property to Rrule class as private readonly array
  - [x] 3.3 Implement getByMonthDay() method returning array of day values
  - [x] 3.4 Update Rrule constructor to accept ByMonthDayNode parameter
  - [x] 3.5 Maintain immutability pattern while supporting new property
  - [x] 3.6 Verify all Rrule object tests pass

- [x] 4. Implement Month Length and Date Validation Logic
  - [x] 4.1 Write tests for month length calculation including leap year handling
  - [x] 4.2 Create helper method for calculating days in month considering leap years
  - [x] 4.3 Implement negative day value resolution to positive day numbers
  - [x] 4.4 Add date validation logic to skip invalid day/month combinations
  - [x] 4.5 Create comprehensive test cases for edge cases (Feb 29, April 31, etc.)
  - [x] 4.6 Verify all date validation tests pass

- [x] 5. Enhance OccurrenceGenerator for BYMONTHDAY Filtering
  - [x] 5.1 Write tests for occurrence generation with BYMONTHDAY patterns
  - [x] 5.2 Extend DefaultOccurrenceGenerator to apply BYMONTHDAY filtering
  - [x] 5.3 Implement BYMONTHDAY logic for FREQ=MONTHLY patterns
  - [x] 5.4 Implement BYMONTHDAY logic for FREQ=YEARLY patterns
  - [x] 5.5 Integrate month length validation during occurrence generation
  - [x] 5.6 Handle empty result sets gracefully when no valid dates exist
  - [x] 5.7 Verify all occurrence generation tests pass

- [x] 6. Create Comprehensive Integration Tests
  - [x] 6.1 Write integration tests for complete BYMONTHDAY workflows
  - [x] 6.2 Test combinations of BYMONTHDAY with existing parameters (COUNT, UNTIL, INTERVAL)
  - [x] 6.3 Create test cases for multiple BYMONTHDAY values (comma-separated lists)
  - [x] 6.4 Test negative BYMONTHDAY values across different months and leap years
  - [x] 6.5 Verify error handling for invalid BYMONTHDAY parameter combinations
  - [x] 6.6 Verify all integration tests pass