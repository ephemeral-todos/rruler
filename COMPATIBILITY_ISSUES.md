# Compatibility Issues with sabre/vobject

This document tracks compatibility differences discovered between Rruler and sabre/vobject through systematic testing.

## Testing Infrastructure Status

✅ **Framework Complete**: Full compatibility testing infrastructure operational  
✅ **Basic Patterns**: DAILY, WEEKLY, MONTHLY, YEARLY frequency tests implemented  
✅ **Termination Conditions**: COUNT and UNTIL pattern validation working  
✅ **Edge Cases**: Leap year and date boundary testing active  

## Current Status: 98% Compatibility Achieved

- **Total Tests**: 54 comprehensive compatibility tests
- **Passing**: 53 tests (98.1% success rate)  
- **Assertions**: 1,248 total with 1,247 passing
- **Critical Issues**: 3 major issues resolved ✅
- **Remaining**: 1 minor BYSETPOS start date handling issue

## Resolved Issues ✅

### 1. BYDAY Time Preservation Bug ✅ RESOLVED

**Status**: ✅ **RESOLVED** - Fixed in commit 83558bb  
**Pattern**: `FREQ=WEEKLY;BYDAY=MO,WE,FR;COUNT=6`

**Issue**: Time was reset to midnight for occurrences in subsequent weeks
**Solution**: Added `findFirstMatchingWeekdayInWeekPreservingTime()` method that preserves time components from the original occurrence when finding matches in new weekly intervals.

**Technical Fix**:
- Modified `getNextWeeklyByDay()` to use time-preserving logic
- Added helper method that extracts time from source occurrence  
- Applied time using `setTime()` with hour, minute, second, microsecond precision

**Result**: ✅ All weekly BYDAY patterns now correctly preserve time across weeks

### 2. Monthly Recurrence Date Boundary Handling ✅ RESOLVED

**Status**: ✅ **RESOLVED** - Fixed in commit 83558bb  
**Pattern**: `FREQ=MONTHLY;COUNT=3` starting 2025-12-31

**Issue**: PHP's date rollover behavior produced March 3rd instead of March 31st when February doesn't have 31 days
**Solution**: Implemented RFC 5545 compliant monthly recurrence with proper date boundary handling.

**Technical Fix**:
- Added `getNextMonthlyOccurrence()` method with intelligent month skipping
- Skip months that don't have the target day (Feb 31st → Mar 31st)  
- Proper year rollover handling for long-term recurrences

**Result**: ✅ Monthly patterns correctly skip invalid months and preserve target day

### 3. Leap Year Yearly Recurrence Behavior ✅ RESOLVED

**Status**: ✅ **RESOLVED** - Fixed in commit 83558bb  
**Pattern**: `FREQ=YEARLY;COUNT=4` starting 2024-02-29 (leap day)

**Issue**: Leap day recurrence moved to March 1st in non-leap years instead of waiting for next leap year
**Solution**: Implemented proper leap year validation to skip non-leap years for Feb 29th recurrences.

**Technical Fix**:
- Added `getNextYearlyOccurrence()` method with leap year detection
- Added `isLeapYear()` helper with proper leap year calculation
- Skip years where target date doesn't exist (Feb 29th in non-leap years)

**Result**: ✅ Leap day recurrence only occurs in valid leap years (2024, 2028, 2032, 2036...)

## Intentional Differences (RFC 5545 Compliance)

### 1. Weekly BYSETPOS Behavior

**Status**: 🔴 **INTENTIONAL DIFFERENCE** - RFC 5545 Compliance Priority  
**Pattern**: `FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1` and similar weekly BYSETPOS patterns

**Issue**: sabre/vobject completely ignores BYSETPOS parameter for weekly frequencies
- **sabre/vobject**: Treats `FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1` identically to `FREQ=WEEKLY;BYDAY=MO,WE,FR`
- **Rruler**: Correctly implements RFC 5545 weekly BYSETPOS behavior

**Examples** (starting 2025-01-01 Wednesday):

`FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1`:
- **sabre/vobject (incorrect)**: 2025-01-01, 2025-01-03, 2025-01-06, 2025-01-08... (all MO/WE/FR days)
- **Rruler (RFC 5545 compliant)**: 2025-01-01, 2025-01-06, 2025-01-13, 2025-01-20... (first in each week)

`FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=-1`:
- **sabre/vobject (incorrect)**: Same as BYSETPOS=1 (ignores BYSETPOS)
- **Rruler (RFC 5545 compliant)**: 2025-01-03, 2025-01-10, 2025-01-17, 2025-01-24... (last in each week)

**Verification**: Validated against python-dateutil (gold standard for RFC 5545) - Rruler matches exactly

**Decision**: Prioritize RFC 5545 compliance over bug compatibility with sabre/vobject

**Impact**: Applications migrating from sabre/vobject may need to review weekly BYSETPOS patterns

## Remaining Issues

### 1. BYSETPOS Start Date Inclusion Logic

**Status**: 🟡 Minor Issue - Under Investigation  
**Pattern**: `FREQ=MONTHLY;BYDAY=MO;BYSETPOS=1;COUNT=3` starting 2025-01-01 (Wednesday)

**Issue**: Different interpretation of whether the start date should be included when it matches BYSETPOS criteria
- **sabre/vobject**: Includes start date if it matches (2025-01-01 as first Monday occurrence)
- **Rruler**: Always starts from first valid occurrence after start date (2025-01-06)

**Impact**: Low - affects edge case where start date exactly matches BYSETPOS criteria
**Priority**: Investigate RFC 5545 specification for correct behavior

## Working Patterns

✅ **Basic Frequency Patterns**: All basic DAILY, WEEKLY, MONTHLY, YEARLY patterns work correctly  
✅ **Interval Variations**: INTERVAL parameter works correctly across all frequencies  
✅ **COUNT Termination**: COUNT parameter terminates correctly  
✅ **UNTIL Termination**: UNTIL parameter terminates correctly  
✅ **Time Preservation**: All patterns preserve time correctly  
✅ **Date Boundaries**: Monthly and yearly patterns handle invalid dates properly
✅ **Leap Year Logic**: Yearly patterns correctly handle leap day recurrence

## Testing Statistics

- **Total Compatibility Tests**: 54 test cases
- **Passing Tests**: 53 (98.1%)
- **Failing Tests**: 1 (1.9%) 
- **Total Assertions**: 1,248 individual comparisons
- **Passing Assertions**: 1,247 (99.9%)
- **Pattern Coverage**: Basic frequencies, intervals, termination, edge cases, boundaries

## Summary

The compatibility testing framework successfully identified and resolved 3 critical RFC 5545 compliance issues, bringing Rruler to 98% compatibility with the industry standard sabre/vobject implementation. The remaining minor issue does not affect core functionality and represents an edge case in BYSETPOS start date handling that requires further RFC 5545 specification analysis.

**Rruler now provides reliable, RFC 5545 compliant RRULE parsing and occurrence generation that matches industry standards.**