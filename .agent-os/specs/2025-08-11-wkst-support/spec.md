# Spec Requirements Document

> Spec: WKST Support
> Created: 2025-08-11
> Status: Complete

## Overview

Implement WKST (Week Start Day) parameter support in the rruler library to allow users to configure which day of the week is considered the start of a week for RRULE calculations. This feature will ensure proper week-based recurrence calculations for different cultural and business contexts where weeks may start on Sunday, Monday, or other days.

## User Stories

### Calendar System Developer

As a calendar system developer, I want to configure week start days using WKST parameter, so that weekly recurrences align with my users' cultural and business week conventions.

**Workflow:** The developer can specify WKST=SU for Sunday-start weeks in regions like the US, or WKST=MO for Monday-start weeks in most European countries. The RRULE parser validates the WKST value and adjusts all week-based calculations accordingly, ensuring BYDAY patterns, BYWEEKNO selections, and weekly intervals respect the configured week start day.

### International Application Developer

As an international application developer, I want WKST to work correctly with BYWEEKNO and BYDAY parameters, so that recurring events align properly with ISO 8601 week numbering and regional week conventions.

**Workflow:** When parsing RRULEs like "FREQ=YEARLY;BYWEEKNO=1;BYDAY=MO;WKST=SU", the system correctly calculates which Monday falls in the first week of the year according to Sunday-start week numbering, ensuring accurate occurrence generation for complex international scheduling requirements.

### Business Scheduling Developer

As a business scheduling developer, I want WKST to default to Monday (MO) when not specified, so that the library follows RFC 5545 standards while allowing explicit configuration for different business week models.

**Workflow:** When WKST is omitted from an RRULE, the system automatically uses Monday as the week start day. When specified (e.g., WKST=SU), all weekly calculations adjust to use Sunday as the first day of the week, ensuring business rules align with organizational week structures.

## Spec Scope

1. **WKST Parameter Parsing** - Parse and validate WKST values (SU, MO, TU, WE, TH, FR, SA) in RRULE strings
2. **Week Start Day Configuration** - Apply WKST settings to modify week boundary calculations throughout the occurrence generation system
3. **BYDAY Integration** - Ensure BYDAY patterns respect WKST settings for proper weekday positioning within weeks
4. **BYWEEKNO Compatibility** - Adjust BYWEEKNO week number calculations to align with configured week start days
5. **Default WKST Behavior** - Implement RFC 5545 default behavior of WKST=MO when parameter is not specified

## Out of Scope

- Timezone-specific week start day defaults
- Calendar system integration beyond RRULE parsing
- Localization of weekday names or cultural week conventions
- Retroactive WKST changes to existing parsed RRULEs

## Expected Deliverable

1. **WKST Parameter Support** - Parser correctly handles WKST=SU through WKST=SA values in RRULE strings
2. **Week Calculation Accuracy** - All weekly, monthly, and yearly recurrence patterns properly respect configured week start days
3. **RFC 5545 Compliance** - WKST behavior matches RFC 5545 specification including proper default handling and parameter validation

## Spec Documentation

- Tasks: @.agent-os/specs/2025-08-11-wkst-support/tasks.md
- Technical Specification: @.agent-os/specs/2025-08-11-wkst-support/sub-specs/technical-spec.md