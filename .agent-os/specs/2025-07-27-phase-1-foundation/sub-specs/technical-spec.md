# Technical Specification

This is the technical specification for the spec detailed in @.agent-os/specs/2025-07-27-phase-1-foundation/spec.md

> Created: 2025-07-27
> Version: 1.0.0

## Technical Requirements

- PHP 8.3+ compatibility with strict typing
- PSR-4 autoloading structure under `Rruler` namespace
- AST-based parser with tokenizer for RRULE string input
- Comprehensive validation with specific error messages
- 100% test coverage for all parsing functionality
- Static analysis passing at PHPStan level max
- Code formatting following PER-CS and Symfony standards

## Approach Options

**Option A:** Simple regex-based parser
- Pros: Quick to implement, straightforward
- Cons: Hard to extend, limited error reporting, brittle

**Option B:** AST-based parser with tokenizer (Selected)
- Pros: Extensible, better error reporting, professional architecture
- Cons: More complex initial implementation

**Option C:** Use existing parsing library
- Pros: Less code to write
- Cons: External dependency conflicts with minimal dependency goal

**Rationale:** AST-based approach provides the extensibility needed for future phases while maintaining clean architecture and excellent error reporting capabilities.

## External Dependencies

**Production Dependencies:** None (maintaining minimal dependency principle)

**Development Dependencies:**
- **phpunit/phpunit** - Testing framework for comprehensive test coverage
- **phpstan/phpstan** - Static analysis at maximum level
- **friendsofphp/php-cs-fixer** - Code formatting with PER-CS and Symfony rules
- **justification:** Essential development tools that don't affect production usage

## Implementation Details

### Project Structure
```
src/
├── Parser/
│   ├── RruleParser.php
│   ├── Tokenizer.php
│   └── Ast/
│       ├── Node.php
│       ├── FrequencyNode.php
│       ├── IntervalNode.php
│       ├── CountNode.php
│       └── UntilNode.php
├── Exception/
│   ├── ParseException.php
│   └── ValidationException.php
└── Rrule.php (main entry point)
```

### Core Classes
- **RruleParser**: Main parser class implementing AST-based parsing
- **Tokenizer**: Breaks RRULE string into meaningful tokens
- **AST Nodes**: Represent parsed RRULE components in tree structure
- **Rrule**: Immutable value object representing parsed RRULE