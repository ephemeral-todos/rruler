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
  - [Advanced BYSETPOS Business Patterns](#advanced-bysetpos-business-patterns)
  - [Multi-Parameter Complex Patterns](#multi-parameter-complex-patterns)
  - [Error Handling & Validation](#error-handling--validation)
  - [Date Range Filtering](#date-range-filtering)
  - [iCalendar Context Parsing](#icalendar-context-parsing)
- [Compatibility & Migration](#compatibility--migration)

## Why Rruler?

### Focused Approach
Unlike complete WebDAV/CalDAV libraries, Rruler provides a **focused solution** specifically for RRULE parsing and occurrence calculation. This results in:

- 🎯 **Simple integration** - Just RRULE parsing, nothing more
- 🚀 **Better performance** - No unnecessary WebDAV/CalDAV overhead  
- 📦 **Minimal dependencies** - Modern PHP 8.3+ with zero production dependencies
- 🧪 **Comprehensive testing** - 1,033+ tests with 98.7% sabre/dav compatibility

### Modern PHP Implementation
- **PHP 8.3+** - Leveraging modern language features and type safety
- **AST-based parser** - Better extensibility and accuracy than regex-based solutions
- **Immutable value objects** - Reliable and predictable behavior
- **Strict RFC 5545 compliance** - Comprehensive validation and error handling

### Supported RRULE Features
- **Core Parameters**: FREQ, INTERVAL, COUNT, UNTIL
- **Advanced Parameters**: BYDAY, BYMONTHDAY, BYMONTH, BYWEEKNO, BYSETPOS
- **iCalendar Context**: Comprehensive VEVENT and VTODO parsing with enhanced error recovery
- **Compatibility**: 98.7% compatible with sabre/dav (industry-leading compatibility rate)
- **Edge Case Handling**: Leap years, month boundaries, timezone support with extended format support

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

*Note: Rruler maintains 98.7% compatibility with sabre/dav - you can confidently migrate or use both libraries together with documented differences representing RFC 5545 compliance vs industry standards.*

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

// Advanced BYWEEKNO pattern: ISO week-based scheduling
$rrule = $rruler->parse('FREQ=YEARLY;BYWEEKNO=13,26,39,52');
$start = new DateTimeImmutable('2024-01-01 09:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 8) as $sprint) {
    echo "Quarterly sprint: " . $sprint->format('Y-m-d \(W\eek W\)') . "\n";
}
```

### Advanced BYSETPOS Business Patterns

```php
// First Monday of each quarter for executive meetings
$rrule = $rruler->parse('FREQ=MONTHLY;INTERVAL=3;BYDAY=MO;BYSETPOS=1');
$start = new DateTimeImmutable('2024-01-01 10:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 4) as $meeting) {
    echo "Executive meeting: " . $meeting->format('Y-m-d l') . "\n";
}

// Last working day of each month for reports
$rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=MO,TU,WE,TH,FR;BYSETPOS=-1');
$start = new DateTimeImmutable('2024-01-31 17:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 6) as $reportDue) {
    echo "Monthly report due: " . $reportDue->format('Y-m-d l') . "\n";
}
```

### Multi-Parameter Complex Patterns

```php
// Board meetings: 2nd Thursday of March, June, September, December
$rrule = $rruler->parse('FREQ=YEARLY;BYMONTH=3,6,9,12;BYDAY=TH;BYSETPOS=2');
$start = new DateTimeImmutable('2024-03-14 14:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 8) as $boardMeeting) {
    echo "Board meeting: " . $boardMeeting->format('Y-m-d l \a\t H:i') . "\n";
}

// First and third Friday of every month (bi-weekly team sync)
$rrule = $rruler->parse('FREQ=MONTHLY;BYDAY=FR;BYSETPOS=1,3');
$start = new DateTimeImmutable('2024-01-05 15:00:00');

foreach ($generator->generateOccurrences($rrule, $start, 12) as $sync) {
    echo "Team sync: " . $sync->format('Y-m-d l') . "\n";
}
```

### Error Handling & Validation

```php
use EphemeralTodos\Rruler\Exception\ValidationException;
use EphemeralTodos\Rruler\Exception\ParseException;

// Robust parsing with comprehensive validation
function parseRruleWithValidation(string $rruleString): ?Rrule
{
    try {
        $rruler = new Rruler();
        return $rruler->parse($rruleString);
    } catch (ValidationException $e) {
        echo "Validation error: " . $e->getMessage() . "\n";
        // Handle specific validation errors (invalid FREQ, BYSETPOS without BY* rules, etc.)
        return null;
    } catch (ParseException $e) {
        echo "Parse error: " . $e->getMessage() . "\n";
        // Handle malformed RRULE strings
        return null;
    }
}

// Example usage with error recovery
$rruleString = 'FREQ=WEEKLY;BYDAY=MO,WE,FR;BYSETPOS=1'; // Valid pattern
$rrule = parseRruleWithValidation($rruleString);

if ($rrule !== null) {
    $occurrences = $generator->generateOccurrences($rrule, new DateTimeImmutable('2024-01-01'), 5);
    foreach ($occurrences as $occurrence) {
        echo "Valid occurrence: " . $occurrence->format('Y-m-d l') . "\n";
    }
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

### iCalendar Context Parsing

```php
use EphemeralTodos\Rruler\IcalParser\IcalParser;

// Parse complete iCalendar files with automatic context extraction
$icalParser = new IcalParser();
$icalData = file_get_contents('calendar.ics');
$contexts = $icalParser->parseCalendar($icalData);

foreach ($contexts as $context) {
    echo "Event: " . $context->getSummary() . "\n";
    echo "RRULE: " . $context->getRrule()->toString() . "\n";
    
    // Generate occurrences using extracted context
    $occurrences = $generator->generateOccurrences(
        $context->getRrule(), 
        $context->getStartDate(),
        10
    );
    
    foreach ($occurrences as $occurrence) {
        echo "  - " . $occurrence->format('Y-m-d H:i:s') . "\n";
    }
}

// Handle multi-component enterprise calendar files
$enterpriseCalendar = file_get_contents('enterprise-calendar.ics'); // 100+ components
$contexts = $icalParser->parseCalendar($enterpriseCalendar);

echo "Parsed " . count($contexts) . " calendar components:\n";
foreach ($contexts as $context) {
    if ($context->getComponentType() === 'VEVENT') {
        echo "Meeting: " . $context->getSummary() . " (" . $context->getRrule()->toString() . ")\n";
    } elseif ($context->getComponentType() === 'VTODO') {
        echo "Task: " . $context->getSummary() . " (Due: " . $context->getStartDate()->format('Y-m-d') . ")\n";
    }
}
```

**Enhanced iCalendar Features:**
- **Multi-component processing** - Handle 100+ VEVENT/VTODO components in single files
- **Extended format support** - Compatible with Outlook, Google Calendar, Apple Calendar formats
- **Error recovery** - Gracefully handles malformed real-world calendar data
- **100% sabre/vobject compatibility** - Validated against industry standard iCalendar parser

**Supported Calendar Applications:**
```php
// Microsoft Outlook exports (with BOM, timezone variations)
$outlookData = $icalParser->parseCalendar($outlookExport); // ✅ Full support

// Google Calendar exports (strict RFC 5545 compliance)
$googleData = $icalParser->parseCalendar($googleExport); // ✅ Full support  

// Apple Calendar exports (nested component variations)
$appleData = $icalParser->parseCalendar($appleExport); // ✅ Full support
```

## Compatibility & Migration

### sabre/dav Compatibility
Rruler achieves **98.7% compatibility** with sabre/dav, representing industry-leading RRULE compliance. Our comprehensive test suite with 1,033+ tests validates:

- **Compatible occurrence generation** for 98.7% of RRULE patterns (3,650+ of 3,697 test cases)
- **Documented differences** represent RFC 5545 vs industry standard interpretations
- **Compatible error handling** for invalid RRULE strings  
- **Performance parity** with optimized algorithms
- **Edge case handling** including leap years, timezone boundaries, and complex patterns

**Testing Infrastructure:**
```bash
# Test current compatibility status
composer test:sabre-dav-incompatibility
just test-sabre-dav-incompatibility
```

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
- 📅 **Enhanced iCalendar processing** beyond basic RRULE parsing
- 🔧 **Extended format support** for real-world calendar applications
- 🛡️ **Advanced error recovery** for malformed calendar data
- 🧪 **Better testability** with clear separation of concerns

### Links & Resources
- **RFC 5545 Specification**: [IETF RFC 5545](https://tools.ietf.org/html/rfc5545#section-3.3.10) 
- **Source Code**: [GitHub Repository](https://github.com/simensen/ephemeral-todos-rruler)
- **Issue Tracker**: [Report Issues](https://github.com/simensen/ephemeral-todos-rruler/issues)
- **sabre/dav Project**: [sabre.io](https://sabre.io/) (for full WebDAV/CalDAV needs)
