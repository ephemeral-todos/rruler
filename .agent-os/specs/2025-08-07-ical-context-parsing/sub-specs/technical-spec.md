# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-07-ical-context-parsing/spec.md

> Created: 2025-08-07
> Version: 1.0.0

## Technical Requirements

### iCalendar Parser Architecture

- **IcalParser Class** - Main entry point for parsing iCalendar strings containing VEVENT/VTODO components
- **ComponentExtractor** - Extract individual VEVENT/VTODO components from iCalendar data
- **LineUnfolder** - Handle iCalendar line unfolding according to RFC 5545 (75-character line length limits)
- **PropertyParser** - Parse individual iCalendar properties with parameters (e.g., DTSTART;TZID=UTC:20250107T100000Z)

### Value Objects and Data Structure

- **IcalComponent** - Immutable value object representing a parsed VEVENT or VTODO component
- **IcalProperty** - Represent individual properties (RRULE, DTSTART, SUMMARY, etc.) with parameters
- **ComponentType** - Enum for VEVENT, VTODO component types
- **DateTimeContext** - Context object containing DTSTART, timezone information for occurrence generation

### RRULE Integration

- **Seamless Parser Integration** - IcalParser uses existing RruleParser internally for RRULE property parsing
- **Context-Aware Generation** - Extend OccurrenceGenerator to accept DateTimeContext for proper DTSTART handling
- **Backward Compatibility** - Existing Rruler API remains unchanged, new iCalendar methods are additive

### Date/DateTime Handling

- **RFC 5545 Date Formats** - Support DATE (YYYYMMDD) and DATE-TIME (YYYYMMDDTHHMMSSZ) formats
- **Timezone Recognition** - Basic UTC (Z suffix) and local time recognition
- **DTSTART Priority** - Use DTSTART from VEVENT/VTODO as occurrence calculation starting point
- **DUE Property Support** - Handle VTODO DUE property as alternative to DTSTART

### Error Handling and Validation

- **Malformed iCalendar Handling** - Graceful handling of invalid iCalendar syntax with descriptive error messages
- **Missing Required Properties** - Validate presence of DTSTART (or DUE for VTODO) when RRULE is present
- **Component Type Validation** - Only process VEVENT and VTODO components, ignore others
- **Property Filtering** - Extract only properties relevant to recurrence calculation

## Approach

### Phase 1: Basic iCalendar Parsing Infrastructure

1. **Line-Level Parsing** - Implement RFC 5545 compliant line unfolding and property extraction
2. **Component Extraction** - Parse BEGIN:VEVENT/VTODO to END:VEVENT/VTODO blocks
3. **Property Parsing** - Extract property names, values, and parameters from iCalendar lines

### Phase 2: Integration with Existing RRULE Parser

1. **Property Integration** - Use existing RruleParser for RRULE property values
2. **Context Building** - Combine RRULE parsing with DTSTART/DUE context extraction
3. **API Design** - Create intuitive API that mirrors existing Rruler interface

### Phase 3: Advanced Features and Validation

1. **sabre/dav Compatibility Testing** - Test against sabre/vobject for result validation
2. **Edge Case Handling** - Handle malformed iCalendar data gracefully
3. **Performance Optimization** - Optimize parsing for large iCalendar datasets

## External Dependencies

**Development Dependencies Only:**
- **sabre/vobject** - For compatibility testing and result validation (development/testing only)
- **Justification:** Industry-standard library for comparing Rruler results against established implementation

**Production Dependencies:**
- None - Maintain zero production dependency requirement