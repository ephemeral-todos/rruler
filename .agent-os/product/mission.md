# Product Mission

> Last Updated: 2025-08-02
> Version: 1.0.0

## Pitch

Rruler is a standalone RFC 5545 Recurrence Rule (RRULE) Parser and Occurrence Dates Calculator that helps developers building TODO applications and calendar systems by providing comprehensive support for complex recurring patterns with strict validation and error handling.

## Users

### Primary Customers

- **PHP Developers**: Building TODO, calendar, or scheduling applications
- **Calendar System Developers**: Needing RFC 5545 compliance for recurring events

### User Personas

**PHP Application Developer** (25-45 years old)
- **Role:** Full-stack Developer, Backend Developer
- **Context:** Building web applications with recurring event functionality
- **Pain Points:** Complex RRULE parsing, unreliable occurrence calculation, outdated libraries
- **Goals:** Simple integration, accurate RFC compliance, comprehensive test coverage

**Calendar System Architect** (30-50 years old)
- **Role:** Technical Lead, System Architect
- **Context:** Designing enterprise calendar or scheduling systems
- **Pain Points:** Legacy dependencies, incomplete RRULE support, poor documentation
- **Goals:** Modern PHP solution, full RFC 5545 support, maintainable codebase

## The Problem

### Complex RRULE Parsing is Error-Prone

Implementing RFC 5545 recurrence rules from scratch is complex and time-consuming, leading to bugs and incomplete feature support. Existing solutions like sabre/dav are too heavy for simple RRULE needs.

**Our Solution:** Focused, modern PHP library dedicated solely to RRULE parsing and occurrence calculation.

### Existing Libraries are Outdated or Abandoned

Current PHP RRULE libraries are either based on aging code or have other limitations and maintenance issues.

**Our Solution:** Fresh implementation with modern PHP practices, comprehensive testing, and focused scope.

### RFC 5545 Compliance is Difficult to Achieve

Ensuring proper compliance with RFC 5545 specifications requires deep understanding of edge cases and complex validation rules that are easy to miss in custom implementations.

**Our Solution:** Strict RFC 5545 compliance with comprehensive validation and error handling.

## Differentiators

### Focused Scope

Unlike sabre/dav which handles entire WebDAV/CalDAV ecosystems, we provide a focused solution specifically for RRULE parsing and occurrence calculation. This results in simpler integration and better performance.

### Modern PHP Implementation

Built for PHP 8.3+, leveraging modern language features and best practices. This provides better type safety, performance, and developer experience compared to legacy solutions.

### AST-Based Parser Architecture

Implements an Abstract Syntax Tree approach for parsing, providing better extensibility and more accurate parsing compared to regex-based solutions.

## Key Features

### Core Features

- **RRULE Parser:** Parse RFC 5545 recurrence rule strings with full validation
- **Recurrence Calculator:** Generate occurrence dates from RRULE between specified date ranges
- **RRULE Validation:** Strict input validation and comprehensive error handling
- **Basic RRULE Patterns:** Complete support for DAILY, WEEKLY, MONTHLY, YEARLY patterns

### Advanced Features

- **Complex RRULE Support:** BYSETPOS, BYWEEKNO, and other advanced RFC 5545 features
- **Occurrence Queries:** Check if specific DateTime represents a valid occurrence
- **Flexible Date Filtering:** Filter occurrences by start/end times and occurrence counts
- **RFC 5545 Context Parsing:** Parse related RFC 5545 data like VTODO, VEVENT, VCALENDAR

### Integration Features

- **Testing Utilities:** Predefined test scenarios for common RRULE patterns
- **Validation Against sabre/dav:** Comprehensive testing to ensure compatibility with established solutions