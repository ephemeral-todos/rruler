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
- `composer test:sabre-dav-incompatibility` - Run tests that are known to be incompatible with sabre/dav
- `composer test:documentation-consistency` - Run tests that validate documentation accuracy
- `composer update-docs` - Update documentation statistics to match current test counts
- `composer analyse` - Run PHPStan static analysis
- `composer format` - Format code with PHP-CS-Fixer
- `composer format:check` - Check code formatting without fixing

### Just Commands (alternative interface)
- `just test` - Run all tests
- `just test-sabre-dav-incompatibility` - Run tests that are known to be incompatible with sabre/dav
- `just test-documentation-consistency` - Run tests that validate documentation accuracy
- `just update-docs` - Update documentation statistics to match current test counts
- `just analyse` - Run static analysis
- `just format` - Format code
- `just format-check` - Check formatting
- `just check` - Run all quality checks (analyse + format:check + test)
- `just build` - Set up project dependencies

### Development Scripts
- `scripts/analyze-test-performance.sh` - Analyze PHPUnit test performance (individual and aggregate timing)
- `scripts/update-documentation-stats.php` - Update documentation test count statistics automatically

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
  - `ByMonthNode` - BYMONTH parameter for month selection in yearly patterns

### Occurrence Generation
- `OccurrenceGenerator` interface with `DefaultOccurrenceGenerator` implementation
- `OccurrenceValidator` interface with `DefaultOccurrenceValidator` implementation
- `DateValidationUtils` - Utility class for date validation, leap year handling, and month length calculations

### Testing Structure
- Unit tests in `tests/Unit/` - Test individual classes in isolation
- Integration tests in `tests/Integration/` - Test complete parsing and occurrence generation workflows
- PHPUnit configuration supports separate test suites

### Supported RRULE Features
Currently supports: FREQ, INTERVAL, COUNT, UNTIL, BYDAY, BYMONTHDAY, BYMONTH, BYWEEKNO, BYSETPOS

**Core Parameters:**
- `FREQ` - Frequency (DAILY, WEEKLY, MONTHLY, YEARLY) - Required
- `INTERVAL` - Recurrence interval (every N periods)
- `COUNT` - Maximum number of occurrences (mutually exclusive with UNTIL)
- `UNTIL` - End date for recurrence (mutually exclusive with COUNT)

**Advanced Parameters:**
- `BYDAY` - Weekday specifications with optional positional prefixes (e.g., MO, 1MO, -1FR)
- `BYMONTHDAY` - Days of month selection with positive (1-31) and negative (-1 to -31) values
- `BYMONTH` - Month selection for yearly patterns with values 1-12 (e.g., 3,6,9,12 for quarterly)
- `BYWEEKNO` - ISO 8601 week number selection for yearly patterns (1-53, -1 to -53)
- `BYSETPOS` - Position-based occurrence selection from expanded recurrence sets

The parser validates mutually exclusive parameters (COUNT vs UNTIL), required parameters (FREQ), and handles complex date validation including leap years and varying month lengths.

### sabre/dav Compatibility Testing

The project maintains comprehensive compatibility testing against sabre/dav (the industry standard WebDAV/CalDAV implementation). Currently achieves **98.7% compatibility** with documented differences for edge cases where RFC 5545 compliance differs from sabre/dav behavior.

**Testing Commands:**
- `composer test:sabre-dav-incompatibility` - Run tests that document known incompatibilities
- `just test-sabre-dav-incompatibility` - Same as above using Just

**Understanding Compatibility:**
- **Default tests (`composer test`)** - Exclude incompatibility tests for clean CI runs
- **Incompatibility tests** - Document specific cases where Rruler follows RFC 5545 more strictly than sabre/dav
- **All documented in:** `COMPATIBILITY_ISSUES.md` with detailed explanations

**When to use incompatibility tests:**
- **Investigate compatibility issues** with sabre/dav-based systems
- **Verify current status** of known differences  
- **Document new incompatibilities** discovered during development
- **Validate fixes** for compatibility issues

### Writing Tests
- Write tests according to rules defined here: @~/.claude/instructions/phpunit.md
## Agent OS Documentation

### Files Availability
The @.agent-os and @~/.agent-os directories should definitely be available. If you never think this is not true based on doing a directory listing, ensure the directory listing is not excluding files and directories with a leading `.`. For bash using ls, you should always use `ls -a` or `ls -a <path-to-search>` to get a list of all files and directories, even those that are "hidden" by using a leading `.`.

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

### 🚨 CRITICAL: Documentation Consistency Validation

**Before considering ANY large chunk of work completed** (task, phase, or entire spec), you MUST:

1. **Run documentation consistency tests**: `composer test:documentation-consistency`
2. **If tests fail** (documentation out of date):
   a. Run the documentation update script: `composer update-docs`
   b. Re-run documentation consistency tests: `composer test:documentation-consistency`
   c. Verify tests now pass
3. **Only then consider work complete**

This ensures documentation stays current with actual implementation. The `documentation-consistency` group is excluded from regular test runs to avoid slowing down development, but MUST be validated before completion.

**Examples of when this applies:**
- Completing any Agent OS spec task or phase
- Finishing a significant refactoring
- Adding/removing tests that change test counts
- Before creating PRs for major features

## Important Notes

- Product-specific files in `.agent-os/product/` override any global standards
- User's specific instructions override (or amend) instructions found in `.agent-os/specs/...`
- Always adhere to established patterns, code style, and best practices documented above.

## Project-Specific Workflows and Preferences

### Git
- Generate git commits according to rules defined here: @~/.claude/instructions/git-commits.md
- Use Conventional Commits format for the first line: `type(scope): description` (e.g., `feat: add BYMONTHDAY support`, `fix: handle leap year edge case`)

## Performance Analysis

### Test Performance Monitoring

The project includes a comprehensive test performance analysis script at `scripts/analyze-test-performance.sh` that provides both individual and aggregate test timing analysis.

**Key Features:**
- **Individual Analysis**: Identifies slowest individual test executions with statistical breakdowns
- **Aggregate Analysis**: Groups tests by name to show total time consumption across multiple runs
- **Smart Insights**: Compares individual vs aggregate performance leaders and identifies optimization opportunities

**Usage Examples:**
```bash
# Full analysis (both individual and aggregate)
scripts/analyze-test-performance.sh

# Quiet mode with top 10 tests only
scripts/analyze-test-performance.sh -q -t 10

# Aggregate analysis only (useful for identifying high-volume tests)
scripts/analyze-test-performance.sh -a

# Analyze existing timing data without running tests
scripts/analyze-test-performance.sh --no-run

# Keep timing logs for later analysis
scripts/analyze-test-performance.sh -k
```

**Performance Insights:**
- Tests with data providers may consume more aggregate time than individually slow tests
- Use aggregate analysis to identify optimization opportunities in high-volume test patterns
- Individual analysis helps find complex tests that may benefit from refactoring

**Dependencies:** Requires `bc` command for mathematical calculations (`brew install bc` on macOS)

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
