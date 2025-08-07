# Compatibility Issues with sabre/vobject

This document tracks compatibility differences discovered between Rruler and sabre/vobject through systematic testing.

## Testing Infrastructure Status

✅ **Framework Complete**: Full compatibility testing infrastructure operational  
✅ **Basic Patterns**: DAILY, WEEKLY, MONTHLY, YEARLY frequency tests implemented  
✅ **Termination Conditions**: COUNT and UNTIL pattern validation working  
✅ **Edge Cases**: Leap year and date boundary testing active  

## Identified Issues

### 1. BYDAY Time Preservation Bug

**Status**: 🔴 Critical Compatibility Issue  
**Affects**: FREQ=WEEKLY with BYDAY parameter  
**Pattern**: `FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6`

**Expected Behavior** (sabre/vobject):
```
2025-01-01 10:00:00 Wednesday  (start date)
2025-01-03 10:00:00 Friday     (same week)  
2025-01-06 10:00:00 Monday     (next week)
2025-01-08 10:00:00 Wednesday  (next week)
2025-01-10 10:00:00 Friday     (next week)
2025-01-13 10:00:00 Monday     (next week)
```

**Actual Behavior** (Rruler):
```
2025-01-01 10:00:00 Wednesday  (start date - correct)
2025-01-03 10:00:00 Friday     (same week - correct)
2025-01-06 00:00:00 Monday     (❌ time reset to midnight)
2025-01-08 00:00:00 Wednesday  (❌ time reset to midnight)
2025-01-10 00:00:00 Friday     (❌ time reset to midnight)  
2025-01-13 00:00:00 Monday     (❌ time reset to midnight)
```

**Root Cause**: Rruler's BYDAY implementation preserves time for occurrences within the same week as the start date but resets time to midnight for occurrences in subsequent weeks.

**Impact**: Medium - affects recurring events that need to preserve specific times across weeks.

### 2. Monthly Recurrence Date Boundary Handling

**Status**: 🔴 Critical Compatibility Issue  
**Affects**: FREQ=MONTHLY starting on month boundaries (31st)  
**Pattern**: `FREQ=MONTHLY;COUNT=3` starting 2025-12-31

**Expected Behavior** (sabre/vobject):
```
2025-12-31 23:59:59  (start date)
2026-01-31 23:59:59  (next month with day 31)
2026-03-31 23:59:59  (skip February, next month with day 31)
```

**Actual Behavior** (Rruler):
```
2025-12-31 23:59:59  (start date - correct)
2026-01-31 23:59:59  (next month - correct)
2026-03-03 23:59:59  (❌ March 3rd instead of March 31st)
```

**Root Cause**: Different algorithms for handling dates that don't exist in target months (e.g., Feb 31st). Rruler appears to be applying some form of date adjustment that differs from the RFC 5545 specification.

**Impact**: High - affects monthly recurring events scheduled on boundary dates (29th, 30th, 31st).

### 3. Leap Year Yearly Recurrence Behavior

**Status**: 🔴 Critical Compatibility Issue  
**Affects**: FREQ=YEARLY starting on February 29th (leap day)  
**Pattern**: `FREQ=YEARLY;COUNT=4` starting 2024-02-29

**Expected Behavior** (sabre/vobject):
```
2024-02-29 10:00:00  (leap day start)
2028-02-29 10:00:00  (next leap year - skips non-leap years)
2032-02-29 10:00:00  (next leap year)
2036-02-29 10:00:00  (next leap year)
```

**Actual Behavior** (Rruler):
```
2024-02-29 10:00:00  (leap day start - correct)
2025-03-01 10:00:00  (❌ moves to March 1st in non-leap year)
2026-03-01 10:00:00  (❌ continues with March 1st)
2027-03-01 10:00:00  (❌ continues with March 1st)
```

**Root Cause**: Different interpretation of RFC 5545 for leap day recurrence. sabre/vobject waits for valid Feb 29th dates (leap years only), while Rruler moves to the next available date (March 1st).

**Impact**: High - affects yearly recurring events scheduled on leap day.

## Working Patterns

✅ **Basic Frequency Patterns**: All basic DAILY, WEEKLY, MONTHLY, YEARLY patterns work correctly  
✅ **Interval Variations**: INTERVAL parameter works correctly across all frequencies  
✅ **COUNT Termination**: COUNT parameter terminates correctly  
✅ **UNTIL Termination**: UNTIL parameter terminates correctly  
✅ **Time Preservation**: Simple patterns preserve time correctly  

## Testing Statistics

- **Total Compatibility Tests**: 54 test cases
- **Passing Tests**: 51 (94.4%)
- **Failing Tests**: 3 (5.6%)
- **Issues Identified**: 3 critical compatibility differences
- **Pattern Coverage**: Basic frequencies, intervals, termination, edge cases

## Next Steps

1. **Fix Time Preservation**: Address BYDAY time reset issue in weekly patterns
2. **Fix Monthly Boundaries**: Implement RFC 5545 compliant monthly date handling  
3. **Fix Leap Year Logic**: Align yearly recurrence behavior with industry standard
4. **Expand Testing**: Continue with advanced RRULE parameter testing (Task 3)
5. **Validate Fixes**: Re-run compatibility tests after fixes

## Notes

The compatibility testing framework is working exceptionally well, successfully identifying subtle but important behavioral differences between implementations. These findings provide clear direction for improving Rruler's RFC 5545 compliance.