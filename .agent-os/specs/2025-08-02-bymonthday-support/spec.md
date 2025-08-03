# Spec Requirements Document

> Spec: BYMONTHDAY Support
> Created: 2025-08-02

## Overview

Implement comprehensive BYMONTHDAY parameter support for RRULE parsing and occurrence generation, enabling developers to specify exact days of the month for monthly and yearly recurrence patterns with full RFC 5545 compliance and robust validation for edge cases like month length variations.

## User Stories

### Monthly Recurring Events on Specific Days

As a calendar application developer, I want to create recurring events that occur on specific days of each month (e.g., 1st and 15th), so that users can schedule regular monthly appointments like payroll dates or bill due dates.

**Detailed Workflow:** Developer parses RRULE strings like "FREQ=MONTHLY;BYMONTHDAY=1,15" to generate occurrences on the 1st and 15th of each month. The system handles month length variations automatically, skipping invalid dates (like Feb 30th) and correctly processing leap years.

### End-of-Month Recurring Events

As a financial application developer, I want to create recurring events that occur on the last day of each month using negative BYMONTHDAY values, so that users can schedule end-of-month reports and financial processes.

**Detailed Workflow:** Developer uses RRULE strings like "FREQ=MONTHLY;BYMONTHDAY=-1" to generate occurrences on the last day of each month (31st in January, 28th/29th in February, etc.). The system correctly calculates the last day based on each month's actual length.

### Yearly Events on Specific Month Days

As a event management developer, I want to create yearly recurring events that occur on specific days across different months, so that users can schedule annual events like quarterly reports or seasonal activities.

**Detailed Workflow:** Developer parses RRULE strings like "FREQ=YEARLY;BYMONTH=3,6,9,12;BYMONTHDAY=15" to generate occurrences on the 15th of March, June, September, and December each year. The system validates combinations and generates accurate occurrence dates.

## Spec Scope

1. **BYMONTHDAY Parameter Parsing** - Parse BYMONTHDAY values from RRULE strings with support for positive (1-31) and negative (-1 to -31) day specifications
2. **Multiple Day Values** - Support comma-separated lists of month days (e.g., BYMONTHDAY=1,15,28) for multiple occurrences within each recurrence period
3. **Month Length Validation** - Validate day values against actual month lengths, handling leap years and months with 28, 29, 30, or 31 days
4. **FREQ Integration** - Seamlessly integrate with existing FREQ=MONTHLY and FREQ=YEARLY patterns for comprehensive recurrence support
5. **Negative Day Calculation** - Implement proper calculation of negative day values (-1 = last day, -2 = second-to-last) based on actual month lengths

## Out of Scope

- BYMONTHDAY support for FREQ=WEEKLY or FREQ=DAILY patterns (not specified in RFC 5545 for these frequencies)
- Complex timezone handling beyond basic DateTime functionality
- BYMONTHDAY interaction with BYSETPOS (will be handled in future BYSETPOS implementation)
- Calendar-specific month length calculations (Hebrew, Islamic calendars)

## Expected Deliverable

1. **Functional BYMONTHDAY Parsing** - RRULE strings containing BYMONTHDAY parameters are correctly parsed into AST nodes and Rrule objects
2. **Accurate Occurrence Generation** - Monthly and yearly patterns with BYMONTHDAY generate correct occurrence dates respecting month length constraints
3. **Complete Test Coverage** - All BYMONTHDAY functionality is thoroughly tested including edge cases for leap years, month boundaries, and negative values