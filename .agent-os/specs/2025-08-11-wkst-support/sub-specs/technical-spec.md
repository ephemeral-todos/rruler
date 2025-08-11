# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-11-wkst-support/spec.md

> Created: 2025-08-11
> Version: 1.0.0

## Technical Requirements

### WKST Parameter Parsing
- **WkstNode Class**: Create new AST node class implementing NodeWithChoices interface
- **Valid Values**: Accept exactly seven values: SU, MO, TU, WE, TH, FR, SA
- **Default Behavior**: Return WKST=MO when parameter is not specified in RRULE
- **Validation**: Reject invalid weekday abbreviations with descriptive error messages
- **Parser Integration**: Integrate WkstNode into RruleParser tokenization and AST building

### Week Calculation Engine Updates
- **DateValidationUtils Enhancement**: Add week start day awareness to existing date utilities
- **Week Boundary Logic**: Implement functions to calculate week boundaries based on WKST settings
- **Day-of-Week Mapping**: Create utilities to map Sunday=0 through Saturday=6 based on configured week start
- **ISO 8601 Compatibility**: Ensure BYWEEKNO calculations adjust properly for non-Monday week starts

### Integration with Existing BY* Rules
- **BYDAY Pattern Adjustment**: Modify BYDAY occurrence generation to respect WKST week boundaries
- **BYWEEKNO Recalculation**: Update week number calculations to align with configured week start day
- **Weekly Frequency**: Ensure FREQ=WEEKLY properly increments based on WKST boundaries
- **Monthly/Yearly Patterns**: Adjust monthly and yearly recurrences when they include week-based components

### Rrule Object Enhancement
- **WKST Property**: Add getWeekStart() method to Rrule class returning weekday abbreviation
- **Default Handling**: Return 'MO' when WKST was not specified in original RRULE string  
- **Immutability**: Maintain immutable Rrule object pattern with WKST support
- **Serialization**: Include WKST in RRULE string output when non-default value

## Approach

### Parser Architecture
Follow existing AST node pattern by creating WkstNode that implements NodeWithChoices interface, similar to FrequencyNode. The node will validate input against the seven valid weekday abbreviations and integrate seamlessly with the existing tokenizer and parser infrastructure.

### Week Calculation Strategy
Extend DateValidationUtils with week-aware functions that accept a week start day parameter. Create utility methods for:
- Converting between different week numbering systems
- Finding week boundaries for any given date and week start day
- Mapping relative weekday positions within WKST-adjusted weeks

### Backward Compatibility
Ensure all existing functionality continues to work unchanged by defaulting WKST to Monday (MO) when not specified. All week-related calculations will use the configured or default week start day transparently.

### Testing Strategy
Comprehensive test coverage including:
- WKST parameter parsing validation for all seven weekdays
- Week boundary calculations for each possible week start day
- Integration testing with BYDAY, BYWEEKNO, and weekly frequency patterns
- Edge case testing around year boundaries and leap years
- Compatibility testing against sabre/dav to maintain 98.7% compatibility rate

## Performance Considerations

- **Minimal Overhead**: WKST processing adds negligible performance impact through simple weekday offset calculations
- **Caching Strategy**: Week boundary calculations can be memoized for repeated date ranges
- **Memory Efficiency**: WKST setting stored as simple string property, no additional object overhead