# Spec Tasks

## Tasks

- [ ] 1. Create ByMonthNode AST Class
  - [ ] 1.1 Write tests for ByMonthNode parsing and validation
  - [ ] 1.2 Create ByMonthNode class following existing AST patterns
  - [ ] 1.3 Implement month value validation (1-12)
  - [ ] 1.4 Add support for comma-separated month lists
  - [ ] 1.5 Verify all ByMonthNode tests pass

- [ ] 2. Integrate BYMONTH Parser Support
  - [ ] 2.1 Write tests for BYMONTH parameter parsing in RruleParser
  - [ ] 2.2 Add BYMONTH recognition to tokenizer
  - [ ] 2.3 Integrate ByMonthNode into parser workflow
  - [ ] 2.4 Update Rrule class to store BYMONTH values
  - [ ] 2.5 Verify all parser integration tests pass

- [ ] 3. Implement Yearly Occurrence Filtering
  - [ ] 3.1 Write tests for BYMONTH occurrence generation
  - [ ] 3.2 Extend DefaultOccurrenceGenerator with month filtering
  - [ ] 3.3 Handle DTSTART month inclusion logic
  - [ ] 3.4 Ensure leap year February compatibility
  - [ ] 3.5 Verify all occurrence generation tests pass

- [ ] 4. Integration Testing and Documentation
  - [ ] 4.1 Write comprehensive integration tests for BYMONTH patterns
  - [ ] 4.2 Test edge cases (leap years, invalid months, DTSTART alignment)
  - [ ] 4.3 Update CLAUDE.md with BYMONTH support documentation
  - [ ] 4.4 Verify all tests pass across the complete test suite