# Spec Requirements Document

> Spec: iCalendar Context Parsing
> Created: 2025-08-07
> Status: Complete

## Overview

Implement comprehensive iCalendar (RFC 5545) context parsing to extract RRULE patterns from VEVENT and VTODO components, enabling real-world calendar integration and testing against established CalDAV/iCalendar data sources.

## User Stories

### Calendar Developer Integration

As a PHP developer building calendar applications, I want to parse complete iCalendar data containing VEVENT and VTODO components with embedded RRULEs, so that I can integrate Rruler with existing calendar systems and real-world iCalendar feeds.

**Detailed Workflow:** Developer receives iCalendar data from CalDAV servers, .ics files, or calendar APIs containing VEVENT/VTODO components with RRULE properties. They need to extract the recurrence rules along with essential context like DTSTART for proper occurrence calculation, while ignoring irrelevant iCalendar properties.

### sabre/dav Compatibility Testing

As a library maintainer, I want to validate Rruler results against sabre/dav using identical iCalendar input data, so that I can ensure RFC 5545 compliance and build confidence in the library's accuracy.

**Detailed Workflow:** Parse the same VEVENT/VTODO components with both Rruler and sabre/dav, compare generated occurrences for identical date ranges, and identify any discrepancies for correction.

## Spec Scope

1. **iCalendar Line Parsing** - Parse iCalendar format lines with proper unfolding and parameter handling
2. **VEVENT Component Processing** - Extract RRULE, DTSTART, and other essential properties from VEVENT components
3. **VTODO Component Processing** - Extract RRULE, DTSTART/DUE, and other essential properties from VTODO components
4. **RRULE Integration** - Seamlessly integrate with existing RruleParser for recurrence rule processing
5. **Date Context Parsing** - Parse DTSTART and DUE date/datetime values for occurrence calculation context

## Out of Scope

- Full RFC 5545 property parsing (only RRULE-related properties)
- VCALENDAR container parsing (focus on individual components)
- EXDATE and EXRULE exception handling (future enhancement)
- Time zone processing beyond basic UTC/local recognition
- Property validation beyond RRULE requirements

## Expected Deliverable

1. **iCalendar Parser** - Parse VEVENT and VTODO components from iCalendar strings and extract RRULE with context
2. **Context-Aware Occurrence Generation** - Generate occurrences using both RRULE and DTSTART context from iCalendar data
3. **sabre/dav Compatibility Validation** - Test suite comparing Rruler results with sabre/dav for identical iCalendar inputs

## Spec Documentation

- Tasks: @.agent-os/specs/2025-08-07-ical-context-parsing/tasks.md
- Technical Specification: @.agent-os/specs/2025-08-07-ical-context-parsing/sub-specs/technical-spec.md