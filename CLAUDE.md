## Agent OS Documentation

### Product Context
- **Mission & Vision:** @.agent-os/product/mission.md
- **Technical Architecture:** @.agent-os/product/tech-stack.md
- **Development Roadmap:** @.agent-os/product/roadmap.md
- **Decision History:** @.agent-os/product/decisions.md

### Development Standards
- **Code Style:** @~/.agent-os/standards/code-style.md
- **Best Practices:** @~/.agent-os/standards/best-practices.md

### Project Management
- **Active Specs:** @.agent-os/specs/
- **Spec Planning:** Use `@~/.agent-os/instructions/create-spec.md`
- **Tasks Execution:** Use `@~/.agent-os/instructions/execute-tasks.md`

## Workflow Instructions

When asked to work on this codebase:

1. **First**, check @.agent-os/product/roadmap.md for current priorities
2. **Then**, follow the appropriate instruction file:
   - For new features: @~/.agent-os/instructions/create-spec.md
   - For tasks execution: @~/.agent-os/instructions/execute-tasks.md
3. **Always**, adhere to the standards in the files listed above

## Important Notes

- Product-specific files in `.agent-os/product/` override any global standards
- User's specific instructions override (or amend) instructions found in `.agent-os/specs/...`
- Always adhere to established patterns, code style, and best practices documented above.

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
