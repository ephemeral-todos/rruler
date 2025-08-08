# Rruler

**Focused RFC 5545 Recurrence Rule (RRULE) Parser and Occurrence Calculator for PHP**

Rruler is a standalone RFC 5545 RRULE parser that helps PHP developers building TODO applications and calendar systems by providing comprehensive support for complex recurring patterns with strict validation and error handling.

## Table of Contents

- [Why Rruler?](#why-rruler)
- [When to Use Rruler vs sabre/dav](#when-to-use-rruler-vs-sabredav)  
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Usage](#usage)
  - [Daily Recurring Patterns](#daily-recurring-patterns)
  - [Weekly Recurring Patterns](#weekly-recurring-patterns)
  - [Monthly Recurring Patterns](#monthly-recurring-patterns)
  - [Complex Yearly Patterns](#complex-yearly-patterns)
  - [Error Handling](#error-handling)
  - [Date Range Filtering](#date-range-filtering)
- [Compatibility & Migration](#compatibility--migration)

## Why Rruler?

### Focused Approach
Unlike complete WebDAV/CalDAV libraries, Rruler provides a **focused solution** specifically for RRULE parsing and occurrence calculation. This results in:

- 🎯 **Simple integration** - Just RRULE parsing, nothing more
- 🚀 **Better performance** - No unnecessary WebDAV/CalDAV overhead  
- 📦 **Minimal dependencies** - Modern PHP 8.3+ with zero production dependencies
- 🧪 **Comprehensive testing** - Validated against sabre/dav for compatibility

### Modern PHP Implementation
- **PHP 8.3+** - Leveraging modern language features and type safety
- **AST-based parser** - Better extensibility and accuracy than regex-based solutions
- **Immutable value objects** - Reliable and predictable behavior
- **Strict RFC 5545 compliance** - Comprehensive validation and error handling

### Supported RRULE Features
- **Core Parameters**: FREQ, INTERVAL, COUNT, UNTIL
- **Advanced Parameters**: BYDAY, BYMONTHDAY, BYMONTH, BYWEEKNO, BYSETPOS
- **iCalendar Context**: Parse VEVENT and VTODO components with DTSTART/DUE integration
- **Edge Case Handling**: Leap years, month boundaries, timezone support

## When to Use Rruler vs sabre/dav

**Choose Rruler when:**
- Building TODO or calendar applications that only need RRULE parsing
- Want minimal dependencies and focused functionality
- Need modern PHP 8.3+ implementation with type safety
- Prefer simple integration over full WebDAV/CalDAV ecosystem

**Choose sabre/dav when:**
- Building full WebDAV/CalDAV server implementations
- Need complete RFC 4791/4918 protocol support
- Working with existing sabre/dav infrastructure
- Require server-to-server calendar synchronization

*Note: Rruler is fully compatible with sabre/dav - you can confidently migrate or use both libraries together.*

## Installation

Install Rruler via Composer:

```bash
composer require ephemeral-todos/rruler
```

**Requirements:**
- PHP 8.3, 8.4, or 8.5
- No additional extensions required

## Quick Start

```php
<?php

use EphemeralTodos\Rruler\Rruler;
use EphemeralTodos\Rruler\Occurrence\Adapter\DefaultOccurrenceGenerator;

// Parse an RRULE string
$rruler = new Rruler();
$rrule = $rruler->parse('FREQ=DAILY;COUNT=5');

// Generate occurrences from a start date
$generator = new DefaultOccurrenceGenerator();
$startDate = new DateTimeImmutable('2024-01-01 09:00:00');
$occurrences = $generator->generateOccurrences($rrule, $startDate);

foreach ($occurrences as $occurrence) {
    echo $occurrence->format('Y-m-d H:i:s') . "\n";
}
// Output:
// 2024-01-01 09:00:00
// 2024-01-02 09:00:00
// 2024-01-03 09:00:00
// 2024-01-04 09:00:00
// 2024-01-05 09:00:00
```

## Usage

### Daily Recurring Patterns

```php
// Daily standup meetings, every weekday for 2 weeks
$rrule = $rruler->parse('FREQ=DAILY;BYDAY=MO,TU,WE,TH,FR;COUNT=10');
$start = new DateTimeImmutable('2024-01-01 09:00:00');

foreach ($generator->generateOccurrences($rrule, $start) as $meeting) {
    echo "Daily standup: " . $meeting->format('Y-m-d l') . "\n";
}
```

### Weekly Recurring Patterns  

```php
// Weekly team meetings, every Tuesday at 2 PM
$rrule = $rruler->parse('FREQ=WEEKLY;INTERVAL=1;BYDAY=TU');
$start = new DateTimeImmutable('2024-01-02 14:00:00');

// Get next 8 weeks of meetings
foreach ($generator->generateOccurrences($rrule, $start, 8) as $meeting) {
    echo "Team meeting: " . $meeting->format('Y-m-d H:i') . "\n";
}
```

### Monthly Recurring Patterns

```php
// Monthly reports due on the 15th, ending December 2024
$rrule = $rruler->parse('FREQ=MONTHLY;BYMONTHDAY=15;UNTIL=20241231T235959Z');
$start = new DateTimeImmutable('2024-01-15 09:00:00');

foreach ($generator->generateOccurrences($rrule, $start) as $dueDate) {
    echo "Report due: " . $dueDate->format('Y-m-d') . "\n";
}
```

### Complex Yearly Patterns

```php
// Quarterly business reviews: last Friday of March, June, September, December
$rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=FR;BYSETPOS=-1');
$start = new DateTimeImmutable('2024-03-29 10:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 8) as $review) {
    echo "Quarterly review: " . $review->format('Y-m-d l') . "\n";
}
```

### Error Handling

```php
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Exception\ParseException;

try {
    $rrule = $rruler->parse('FREQ=INVALID;COUNT=5');
} catch (ValidationException $e) {
    echo "Validation error: " . $e->getMessage() . "\n";
} catch (ParseException $e) {
    echo "Parse error: " . $e->getMessage() . "\n";
}
```

### Date Range Filtering

```php
// Generate occurrences within a specific date range
$rrule = $rruler->parse('FREQ=WEEKLY;BYDAY=MO');
$start = new DateTimeImmutable('2024-01-01 09:00:00');
$rangeStart = new DateTimeImmutable('2024-06-01');
$rangeEnd = new DateTimeImmutable('2024-08-31');

foreach ($generator->generateOccurrencesInRange($rrule, $start, $rangeStart, $rangeEnd) as $occurrence) {
    echo "Summer Monday: " . $occurrence->format('Y-m-d') . "\n";
}
```

## Compatibility & Migration

### sabre/dav Compatibility
Rruler has been thoroughly tested against sabre/dav to ensure **100% compatible results** for RRULE parsing and occurrence generation. Our comprehensive test suite validates:

- **Identical occurrence generation** for all supported RRULE patterns
- **Compatible error handling** for invalid RRULE strings  
- **Performance parity** with optimized algorithms
- **Edge case handling** including leap years, timezone boundaries, and complex patterns

### Migration from sabre/dav
If you're currently using sabre/dav only for RRULE parsing, migrating to Rruler is straightforward:

```php
// Before: sabre/dav approach
$vcalendar = Reader::read($calendarData);
$vevent = $vcalendar->VEVENT;
$rrule = $vevent->RRULE->getValue();
$iterator = new EventIterator($vcalendar, $vevent->UID);

// After: Rruler approach  
$rruler = new Rruler();
$generator = new DefaultOccurrenceGenerator();
$rrule = $rruler->parse($rruleString);
$occurrences = $generator->generateOccurrences($rrule, $startDate);
```

**Benefits of migration:**
- 📦 **Reduce dependency footprint** by 90%+ 
- 🚀 **Faster bootstrap time** with focused functionality
- 🛠️ **Modern PHP features** with strict typing and immutable objects
- 🧪 **Better testability** with clear separation of concerns

### Links & Resources
- **RFC 5545 Specification**: [IETF RFC 5545](https://tools.ietf.org/html/rfc5545#section-3.3.10) 
- **Source Code**: [GitHub Repository](https://github.com/simensen/ephemeral-todos-rruler)
- **Issue Tracker**: [Report Issues](https://github.com/simensen/ephemeral-todos-rruler/issues)
- **sabre/dav Project**: [sabre.io](https://sabre.io/) (for full WebDAV/CalDAV needs)
