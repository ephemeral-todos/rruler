# Spec Requirements Document

> Spec: BYWEEKNO Support
> Created: 2025-08-06
> Status: **COMPLETED** ✅
> Completed: 2025-08-06

## Overview

Implement BYWEEKNO parameter support for yearly RRULE patterns to enable week number selection in recurring events. This feature will allow developers to create complex yearly patterns based on ISO week numbers like bi-annual meetings (BYWEEKNO=1,26) or quarterly check-ins (BYWEEKNO=13,26,39,52), completing advanced temporal targeting for yearly recurrence patterns.

## User Stories

### Corporate Calendar Planning

As an enterprise application developer, I want to parse FREQ=YEARLY;BYWEEKNO=1,13,26,39,52 patterns, so that I can generate quarterly business meetings aligned with fiscal calendar weeks rather than specific dates.

The parser should accept comma-separated week numbers (1-53), validate each week number according to ISO 8601 standards, and generate occurrences only in the specified weeks. The occurrence generator should respect DTSTART's day-of-week and time while cycling through the specified weeks each year.

### Project Management Cycles

As a project management system developer, I want to support arbitrary week combinations like BYWEEKNO=1,26 or BYWEEKNO=10,20,30,40,50, so that I can handle custom project review cycles or milestone patterns that align with organizational planning cycles.

The system should validate week values are within 1-53 range (accounting for leap weeks), handle single or multiple weeks, and integrate seamlessly with existing INTERVAL support for multi-year patterns.

## Spec Scope

1. **ByWeekNoNode AST Class** - Create AST node to parse and validate BYWEEKNO parameter values (1-53)
2. **RRULE Parser Integration** - Extend RruleParser to recognize and process BYWEEKNO in yearly patterns
3. **Rrule Value Object Support** - Add getByWeekNo() method to Rrule class for accessing parsed week values
4. **Week Number Calculation** - Implement ISO 8601 week number logic for accurate week-to-date conversion
5. **Occurrence Generation Logic** - Implement week filtering in DefaultOccurrenceGenerator for yearly patterns
6. **Input Validation** - Validate week values are integers 1-53 with clear error messages for invalid input

## Out of Scope

- BYWEEKNO support for non-yearly frequencies (DAILY, WEEKLY, MONTHLY)
- BYWEEKNO interaction with BYSETPOS or other complex BY* combinations (future features)
- BYWEEKNO support in EXRULE patterns
- Custom week start day (WKST) considerations beyond standard ISO 8601
- Timezone-specific week calculations

## Expected Deliverable

1. Parse and generate occurrences for FREQ=YEARLY;BYWEEKNO=1,13,26,39,52 patterns correctly
2. Handle ISO 8601 week numbering including leap weeks and year boundaries accurately
3. Validate and reject invalid week values with descriptive error messages
4. All existing tests continue to pass with new BYWEEKNO functionality integrated