# Spec Requirements Document

> Spec: BYMONTH Support
> Created: 2025-08-06

## Overview

Implement BYMONTH parameter support for yearly RRULE patterns to enable month selection in recurring events. This feature will allow developers to create complex yearly patterns like quarterly occurrences (BYMONTH=3,6,9,12) or custom month combinations, completing the core BY* rule support for yearly recurrence patterns.

## User Stories

### Quarterly Event Scheduling

As a calendar application developer, I want to parse FREQ=YEARLY;BYMONTH=3,6,9,12 patterns, so that I can generate quarterly recurring events like quarterly business reviews or seasonal reports.

The parser should accept comma-separated month values (1-12), validate each month number, and generate occurrences only in the specified months. The occurrence generator should respect DTSTART's day-of-month and time while cycling through the specified months each year.

### Custom Month Combinations

As a scheduling system developer, I want to support arbitrary month combinations like BYMONTH=1,5,9 or BYMONTH=6,12, so that I can handle irregular yearly patterns such as bi-annual events or custom business cycles.

The system should validate month values are within 1-12 range, handle single or multiple months, and integrate seamlessly with existing INTERVAL support for multi-year patterns.

## Spec Scope

1. **ByMonthNode AST Class** - Create AST node to parse and validate BYMONTH parameter values (1-12)
2. **RRULE Parser Integration** - Extend RruleParser to recognize and process BYMONTH in yearly patterns
3. **Rrule Value Object Support** - Add getByMonth() method to Rrule class for accessing parsed month values
4. **Occurrence Generation Logic** - Implement month filtering in DefaultOccurrenceGenerator for yearly patterns
5. **Input Validation** - Validate month values are integers 1-12 with clear error messages for invalid input

## Out of Scope

- BYMONTH support for non-yearly frequencies (DAILY, WEEKLY, MONTHLY)
- BYMONTH interaction with BYWEEKNO or BYSETPOS (future features)
- BYMONTH support in EXRULE patterns
- Timezone-specific month calculations

## Expected Deliverable

1. Parse and generate occurrences for FREQ=YEARLY;BYMONTH=3,6,9,12 patterns correctly
2. Validate and reject invalid month values with descriptive error messages
3. All existing tests continue to pass with new BYMONTH functionality integrated