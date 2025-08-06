# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-08-06-bysetpos-support/spec.md

> Created: 2025-08-06
> Version: 1.0.0

## Technical Requirements

- Extend RruleParser to recognize BYSETPOS parameter with comma-separated integer values
- Implement BySetPosNode AST class with validation for non-zero integers within reasonable bounds
- Add BYSETPOS support to Rrule value object with getBySetPos() and hasBySetPos() methods
- Implement occurrence expansion and selection logic in DefaultOccurrenceGenerator
- Support positive indexing (1, 2, 3...) for selection from beginning of expanded set
- Support negative indexing (-1, -2, -3...) for selection from end of expanded set
- Validate BYSETPOS is only used in combination with other BY* rules (BYDAY, BYMONTHDAY, etc.)
- Integrate with existing occurrence validation and generation pipeline
- Maintain backward compatibility with all existing RRULE functionality

## Approach

- **Parser Layer**: BySetPosNode extends existing AST node structure with integer list validation
- **Value Object**: Rrule class gains BYSETPOS accessors following existing pattern
- **Generator Logic**: Two-phase approach - expand base pattern, then select by position
- **Validation**: Runtime validation of BYSETPOS usage patterns and value ranges
- **Testing**: Unit tests for parser/validation, integration tests for real-world patterns