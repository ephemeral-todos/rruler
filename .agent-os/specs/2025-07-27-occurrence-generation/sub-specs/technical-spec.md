# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-07-27-occurrence-generation/spec.md

> Created: 2025-07-27
> Version: 1.0.0

## Technical Requirements

- Service-based architecture with interfaces and adapters
- Generator-based occurrence calculation for memory efficiency
- DateTimeImmutable integration with timezone support
- COUNT and UNTIL termination logic
- Date range filtering for performance
- Validation of specific DateTime instances against RRULE patterns
- Support for DAILY and WEEKLY frequency patterns only (Phase 2 scope)

## Approach Options

**Option A:** Methods on Rrule object
- Pros: Simple API, everything in one place
- Cons: Violates single responsibility, harder to test, less flexible

**Option B:** Service-based with interfaces and adapters (Selected)
- Pros: Clean separation of concerns, testable, extensible, dependency injection friendly
- Cons: More classes to maintain, slightly more complex setup

**Option C:** Static utility classes
- Pros: Simple to use, no instantiation needed
- Cons: Hard to test, no dependency injection, less flexible

**Rationale:** Service-based approach provides the best separation of concerns, testability, and extensibility while maintaining clean architecture principles.

## External Dependencies

**Production Dependencies:** None (maintaining minimal dependency principle)

**Development Dependencies:**
- No additional dependencies beyond existing PHPUnit, PHPStan, PHP-CS-Fixer setup

## Implementation Details

### Core Architecture

**Interfaces:**
- `OccurrenceGenerator` - Service interface for generating occurrences
- `OccurrenceValidator` - Service interface for validating DateTime instances

**Default Implementations:**
- `DefaultOccurrenceGenerator` - Generator-based implementation
- `DefaultOccurrenceValidator` - Validation implementation using OccurrenceGenerator

### Class Structure
```
src/
├── Occurrence/
│   ├── OccurrenceGenerator.php (interface)
│   ├── OccurrenceValidator.php (interface)
│   └── Adapter/
│       ├── DefaultOccurrenceGenerator.php
│       └── DefaultOccurrenceValidator.php
└── Rrule.php (unchanged)
```

### API Design

**OccurrenceGenerator Interface:**
```php
interface OccurrenceGenerator
{
    // Generate occurrences with optional limit
    public function generateOccurrences(
        Rrule $rrule,
        DateTimeImmutable $start,
        ?int $limit = null
    ): Generator;

    // Generate occurrences within date range
    public function generateOccurrencesInRange(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $rangeStart,
        DateTimeImmutable $rangeEnd
    ): Generator;
}
```

**OccurrenceValidator Interface:**
```php
interface OccurrenceValidator
{
    // Check if specific date is valid occurrence
    public function isValidOccurrence(
        Rrule $rrule,
        DateTimeImmutable $start,
        DateTimeImmutable $candidate
    ): bool;
}
```

**DefaultOccurrenceValidator Implementation:**
```php
final class DefaultOccurrenceValidator implements OccurrenceValidator
{
    public function __construct(
        private OccurrenceGenerator $occurrenceGenerator
    ) {}
    
    // Uses generator to check if candidate appears in sequence
}
```

### Frequency Calculations

**DAILY Pattern:**
- Add interval days to current date using DateTimeImmutable::modify()
- Respect COUNT/UNTIL limits from Rrule
- Handle timezone considerations

**WEEKLY Pattern:**
- Add (interval * 7) days to current date
- Respect COUNT/UNTIL limits from Rrule
- Handle week boundaries properly

### Performance Considerations

- Use PHP generators for lazy evaluation in DefaultOccurrenceGenerator
- Early termination for COUNT and UNTIL conditions
- Efficient date arithmetic using DateTimeImmutable::modify()
- Skip unnecessary calculations when filtering by date range
- DefaultOccurrenceValidator uses generator efficiently to validate candidates