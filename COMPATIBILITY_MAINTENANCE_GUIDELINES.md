# Compatibility Maintenance Guidelines

> Last Updated: 2025-08-10
> Version: 1.0.0

This document provides guidelines for evaluating and handling compatibility differences between Rruler and sabre/vobject implementations.

## Decision Framework

When encountering new compatibility differences, use this decision framework:

### 1. Identify the Type of Difference

**🔍 Analysis Questions:**
- Is this a functional difference (different results) or implementation detail?
- Does the difference affect end-user functionality?
- Is the pattern commonly used in real-world applications?

**📋 Classification:**
- **Critical**: Affects common use cases, produces wrong results
- **Minor**: Edge cases, rarely used patterns
- **Cosmetic**: Different but equivalent results (e.g., date formatting)

### 2. Verify Against RFC 5545 Specification

**📖 RFC 5545 Research:**
1. **Primary Sources**: Check RFC 5545 sections 3.3.10 (Recur Rule) and 3.8.5.3 (RRULE)
2. **Examples**: Look for explicit examples in the specification
3. **Edge Cases**: Determine if RFC 5545 provides clear guidance

**🔬 Validation Methods:**
1. **python-dateutil**: Test against python-dateutil (gold standard implementation)
2. **Multiple Implementations**: Test against other RFC 5545 libraries when possible
3. **Manual Calculation**: For simple patterns, manually verify expected behavior

### 3. Decision Matrix

Use this matrix to determine the appropriate action:

| RFC 5545 Clear? | sabre/vobject Correct? | Action |
|-----------------|------------------------|---------|
| ✅ Yes | ✅ Yes | **Fix Rruler** - Implement correct behavior |
| ✅ Yes | ❌ No | **Document Difference** - RFC compliance priority |
| ❌ Unclear | ✅ Yes | **Match sabre/vobject** - Industry compatibility |
| ❌ Unclear | ❌ No | **Research More** - Find additional references |

### 4. Implementation Guidelines

**🚀 For Rruler Fixes:**
```
1. Write failing test case demonstrating the issue
2. Implement fix with clear comments explaining RFC 5545 reasoning
3. Verify fix doesn't break existing functionality
4. Update relevant documentation
```

**📝 For Documented Differences:**
```
1. Add to COMPATIBILITY_ISSUES.md with clear examples
2. Update test suite with expected failure documentation
3. Include RFC 5545 reference and rationale
4. Provide migration guidance for users switching from sabre/vobject
```

## Testing Standards

### Test Case Requirements

**📋 All compatibility differences must include:**
- [ ] Test case demonstrating the difference
- [ ] Clear documentation of expected vs actual behavior
- [ ] RFC 5545 reference (when applicable)
- [ ] Real-world usage examples
- [ ] Performance impact analysis (if significant)

### Test Documentation Format

```php
/**
 * Test [specific pattern] behavior.
 * 
 * ⚠️ EXPECTED DIFFERENCE: [Brief description]
 * 
 * RFC 5545 Reference: Section X.X.X
 * sabre/vobject behavior: [Description]
 * Rruler behavior: [Description]
 * Rationale: [Why Rruler behaves differently]
 */
public function testSpecificPatternBehavior(): void
{
    // Clear test implementation
}
```

## Documentation Standards

### COMPATIBILITY_ISSUES.md Updates

**🔄 When adding new compatibility issues:**

1. **Add to appropriate section:**
   - `Resolved Issues ✅` - Fixed problems
   - `Intentional Differences` - RFC 5545 compliance differences
   - `Remaining Issues` - Known problems to be fixed

2. **Required information:**
   - **Status**: Clear status indicator (✅ RESOLVED, 🔴 INTENTIONAL, 🟡 INVESTIGATING)
   - **Pattern**: Exact RRULE pattern demonstrating the issue
   - **Issue Description**: Clear explanation of the difference
   - **Examples**: Before/after or comparison examples
   - **Impact**: Assessment of real-world impact
   - **Decision**: Rationale for chosen approach

3. **Example format:**
```markdown
### N. [Issue Name]

**Status**: 🔴 **INTENTIONAL DIFFERENCE** - RFC 5545 Compliance Priority
**Pattern**: `FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1`

**Issue**: [Description of the difference]
- **sabre/vobject**: [Behavior description]
- **Rruler**: [Behavior description]

**Examples**:
[Clear before/after examples]

**Verification**: [How was correctness determined]
**Decision**: [Why this approach was chosen]
**Impact**: [Effect on users migrating from sabre/vobject]
```

## Maintenance Checklist

### Regular Compatibility Reviews

**📅 Quarterly (Every 3 months):**
- [ ] Run complete compatibility test suite
- [ ] Review test failure patterns for new issues
- [ ] Update compatibility rate statistics
- [ ] Review and update documentation accuracy

**📅 Before Major Releases:**
- [ ] Comprehensive compatibility validation
- [ ] Update COMPATIBILITY_ISSUES.md statistics
- [ ] Review intentional differences for continued validity
- [ ] Performance regression testing

### New Issue Evaluation

**🔄 When new compatibility issues are discovered:**

1. **Immediate Assessment** (Same day):
   - [ ] Classify issue severity (Critical/Minor/Cosmetic)
   - [ ] Create failing test case
   - [ ] Determine if existing functionality is affected

2. **Research Phase** (Within 1 week):
   - [ ] RFC 5545 specification research
   - [ ] python-dateutil behavior verification
   - [ ] Real-world usage impact assessment
   - [ ] Decision matrix evaluation

3. **Implementation Phase** (Within 2 weeks):
   - [ ] Implement fix OR document intentional difference
   - [ ] Update test suite with appropriate markers
   - [ ] Update COMPATIBILITY_ISSUES.md
   - [ ] Review impact on related functionality

## Quality Gates

### Compatibility Rate Thresholds

- **Production Release**: >95% effective compatibility rate
- **Development Target**: >98% effective compatibility rate
- **Critical Issues**: 0 unresolved critical compatibility issues

### Documentation Requirements

- **Test Coverage**: All compatibility differences must have test coverage
- **Documentation Coverage**: All differences must be documented in COMPATIBILITY_ISSUES.md
- **Example Coverage**: All differences must include clear examples

## Communication Guidelines

### User-Facing Communications

**🔄 When documenting breaking changes:**
1. Lead with the benefit (RFC 5545 compliance, better accuracy)
2. Provide clear migration examples
3. Explain the rationale in terms of correctness
4. Offer workarounds when possible

**📋 Template for breaking change announcements:**
```
## Weekly BYSETPOS Behavior Change (RFC 5545 Compliance)

**What Changed**: Rruler now correctly implements RFC 5545 weekly BYSETPOS behavior.

**Why**: sabre/vobject has a bug where it ignores BYSETPOS for weekly frequencies. Rruler prioritizes RFC 5545 compliance.

**Migration**: If you're migrating from sabre/vobject and using weekly BYSETPOS patterns:

Before (sabre/vobject):
FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1 → All Mon/Wed/Fri occurrences

After (Rruler):
FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1 → Only Monday each week (first in order)

**Workaround**: For sabre/vobject compatibility, remove BYSETPOS from weekly patterns:
FREQ=WEEKLY;BYDAY=MO,WE,FR (produces all Mon/Wed/Fri occurrences)
```

## Conclusion

These guidelines ensure that Rruler maintains high compatibility with industry standards while prioritizing RFC 5545 specification compliance. The framework provides clear decision-making criteria and documentation standards for handling compatibility differences systematically.

**Key Principles:**
1. **RFC 5545 Compliance First** - When in doubt, follow the specification
2. **Clear Documentation** - All differences must be clearly documented with examples
3. **User Impact Focus** - Consider real-world usage patterns and migration complexity
4. **Systematic Evaluation** - Use consistent criteria for all compatibility decisions