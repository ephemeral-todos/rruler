# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Rruler is a standalone RFC 5545 Recurrence Rule (RRULE) Parser and Occurrence Dates Calculator that helps developers building TODO applications and calendar systems by providing comprehensive support for complex recurring patterns with strict validation and error handling.

Rruler serves PHP developers building calendar and scheduling applications who need reliable RRULE parsing without the complexity of full WebDAV/CalDAV ecosystems. Unlike heavy libraries like sabre/dav, Rruler provides a focused, modern PHP 8.3+ solution with AST-based parsing for better maintainability and extensibility while ensuring strict RFC 5545 compliance.

## Development Commands

The project uses both Composer and Just for task management:

### Core Commands (via Composer)
- `composer test` - Run all tests
- `composer test:unit` - Run unit tests only
- `composer test:integration` - Run integration tests only
- `composer test:coverage` - Run tests with coverage report (requires XDEBUG_MODE=coverage)
- `composer analyse` - Run PHPStan static analysis
- `composer format` - Format code with PHP-CS-Fixer
- `composer format:check` - Check code formatting without fixing

### Just Commands (alternative interface)
- `just test` - Run all tests
- `just analyse` - Run static analysis
- `just format` - Format code
- `just format-check` - Check formatting
- `just check` - Run all quality checks (analyse + format:check + test)
- `just build` - Set up project dependencies

## Architecture

### Core Classes
- `Rruler` - Main entry point that parses RRULE strings using `RruleParser`
- `Rrule` - Immutable value object representing a parsed recurrence rule
- `RruleParser` - Parses RRULE strings into AST nodes then builds `Rrule` objects

### Parser System  
- `Tokenizer` - Breaks RRULE strings into parameter/value pairs
- AST Nodes in `Parser/Ast/` - Each RRULE parameter has its own node class:
  - `FrequencyNode` - Required FREQ parameter (DAILY, WEEKLY, etc.)
  - `IntervalNode` - INTERVAL parameter  
  - `CountNode` - COUNT parameter
  - `UntilNode` - UNTIL parameter
  - `ByDayNode` - BYDAY parameter with weekday specifications
  - `ByMonthDayNode` - BYMONTHDAY parameter for days of month selection

### Occurrence Generation
- `OccurrenceGenerator` interface with `DefaultOccurrenceGenerator` implementation
- `OccurrenceValidator` interface with `DefaultOccurrenceValidator` implementation
- `DateValidationUtils` - Utility class for date validation, leap year handling, and month length calculations

### Testing Structure
- Unit tests in `tests/Unit/` - Test individual classes in isolation
- Integration tests in `tests/Integration/` - Test complete parsing and occurrence generation workflows
- PHPUnit configuration supports separate test suites

### Supported RRULE Features
Currently supports: FREQ, INTERVAL, COUNT, UNTIL, BYDAY, BYMONTHDAY

**Core Parameters:**
- `FREQ` - Frequency (DAILY, WEEKLY, MONTHLY, YEARLY) - Required
- `INTERVAL` - Recurrence interval (every N periods)
- `COUNT` - Maximum number of occurrences (mutually exclusive with UNTIL)
- `UNTIL` - End date for recurrence (mutually exclusive with COUNT)

**Advanced Parameters:**
- `BYDAY` - Weekday specifications with optional positional prefixes (e.g., MO, 1MO, -1FR)
- `BYMONTHDAY` - Days of month selection with positive (1-31) and negative (-1 to -31) values

The parser validates mutually exclusive parameters (COUNT vs UNTIL), required parameters (FREQ), and handles complex date validation including leap years and varying month lengths.

### Writing Tests
- Write tests according to rules defined here: @~/.claude/instructions/phpunit.md
## Agent OS Documentation

### Product Context
- **Mission & Vision:** @.agent-os/product/mission.md
- **Mission (Lite):** @.agent-os/product/mission-lite.md
- **Technical Architecture:** @.agent-os/product/tech-stack.md
- **Development Roadmap:** @.agent-os/product/roadmap.md
- **Decision History:** @.agent-os/product/decisions.md

### Development Standards
- **Code Style:** @~/.agent-os/standards/code-style.md
- **Best Practices:** @~/.agent-os/standards/best-practices.md

### Project Management
- **Active Specs:** @.agent-os/specs/
- **Spec Planning:** Use `@~/.agent-os/instructions/core/create-spec.md`
- **Tasks Execution:** Use `@~/.agent-os/instructions/core/execute-tasks.md`

## Workflow Instructions

When asked to work on this codebase:

1. **First**, check @.agent-os/product/roadmap.md for current priorities
2. **Then**, follow the appropriate instruction file:
   - For new features: @~/.agent-os/instructions/core/create-spec.md
   - For tasks execution: @~/.agent-os/instructions/core/execute-tasks.md
3. **Always**, adhere to the standards in the files listed above

## Important Notes

- Product-specific files in `.agent-os/product/` override any global standards
- User's specific instructions override (or amend) instructions found in `.agent-os/specs/...`
- Always adhere to established patterns, code style, and best practices documented above.

## Project-Specific Workflows and Preferences

### Git
- Generate git commits according to rules defined here: @~/.claude/instructions/git-commits.md
- Use Conventional Commits format for the first line: `type(scope): description` (e.g., `feat: add BYMONTHDAY support`, `fix: handle leap year edge case`)

## Testing Guidelines

### Avoid Structural/Reflection-Based Tests

**DO NOT** write tests that use PHP reflection to verify class structure, method existence, or similar architectural concerns. These tests:

- Provide no behavioral validation
- Create maintenance overhead without value
- Break when refactoring without indicating real problems
- Test implementation details rather than functionality

**Examples to avoid:**
```php
// ❌ Don't write tests like this
public function testClassHasMethod(): void
{
    $reflection = new \ReflectionClass(SomeClass::class);
    $this->assertTrue($reflection->hasMethod('someMethod'));
}

public function testClassIsAbstract(): void
{
    $reflection = new \ReflectionClass(SomeClass::class);
    $this->assertTrue($reflection->isAbstract());
}
```

**Instead, write behavioral tests:**
```php
// ✅ Write tests like this
public function testParseValidRruleReturnsExpectedResult(): void
{
    $parser = new RruleParser();
    $result = $parser->parse('FREQ=DAILY;INTERVAL=2');
    
    $this->assertInstanceOf(Rrule::class, $result);
    $this->assertEquals('DAILY', $result->getFrequency());
    $this->assertEquals(2, $result->getInterval());
}
```

Focus on testing **what the code does**, not **what the code looks like**.

### Test Interface Implementation in Concrete Classes

**DO NOT** create separate test files for interface functionality. Instead, test interface behavior within the concrete class tests that implement the interface.

**Rationale:**
- Interface functionality is meaningless without concrete implementation
- Testing in concrete classes provides real-world usage context
- Reduces test duplication and maintenance overhead
- Focuses on actual behavior rather than abstract contracts

**Examples:**

```php
// ❌ Don't create separate interface tests
class NodeWithChoicesTest extends TestCase
{
    public function testFrequencyNodeImplementsNodeWithChoices(): void
    {
        $choices = FrequencyNode::getChoices();
        $this->assertIsArray($choices);
    }
}

// ✅ Test interface behavior in concrete class tests
class FrequencyNodeTest extends TestCase
{
    public function testGetChoicesReturnsValidFrequencies(): void
    {
        $choices = FrequencyNode::getChoices();
        
        $this->assertEquals(['DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY'], $choices);
        $this->assertContains('DAILY', $choices);
    }
}
```

This approach ensures interface functionality is tested in realistic usage scenarios.
