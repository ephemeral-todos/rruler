# Product Roadmap

> Last Updated: 2025-07-26
> Version: 1.0.0
> Status: Planning

## Phase 1: Foundation & Core Parser (2-3 weeks)

**Goal:** Establish project foundation with basic RRULE parsing capability
**Success Criteria:** Can parse simple DAILY, WEEKLY, MONTHLY, YEARLY patterns

### Must-Have Features

- [ ] Project Setup - Composer package, PHPUnit, PHPStan, PHP-CS-Fixer configuration `M`
- [ ] AST Parser Foundation - Basic tokenizer and AST node structure for RRULE parsing `L`
- [ ] Core RRULE Parser - Parse FREQ, INTERVAL, COUNT, UNTIL basic parameters `L`
- [ ] Basic Validation - Input validation and error handling for malformed RRULE strings `M`
- [ ] Unit Test Foundation - Test framework setup with initial parser tests `M`

### Should-Have Features

- [ ] Development Tools - Justfile/Makefile with common tasks (test, lint, fix) `S`
- [ ] CI Pipeline - GitHub Actions for testing across PHP versions `M`

### Dependencies

- Composer package structure
- PHPUnit testing framework

## Phase 2: Basic Occurrence Generation (2 weeks)

**Goal:** Generate occurrence dates for simple recurrence patterns
**Success Criteria:** Calculate occurrences for basic FREQ patterns with COUNT/UNTIL

### Must-Have Features

- [ ] DateTime Handling - Robust DateTime/DateTimeImmutable integration `M`
- [ ] Basic Occurrence Calculator - Generate occurrences for DAILY, WEEKLY patterns `L`
- [ ] Count/Until Logic - Implement COUNT and UNTIL termination conditions `M`
- [ ] Date Range Filtering - Generate occurrences between specified date ranges `M`
- [ ] Occurrence Validation - Check if specific DateTime is a valid occurrence `M`

### Should-Have Features

- [ ] Performance Optimization - Generator patterns for large occurrence sets `S`
- [ ] Memory Efficiency - Lazy evaluation implementation `S`

### Dependencies

- Phase 1 RRULE parser
- Basic validation framework

## Phase 3: Advanced RRULE Features (3-4 weeks)

**Goal:** Support complex RRULE patterns and advanced RFC 5545 features
**Success Criteria:** Handle BYSETPOS, BYWEEKNO, BYMONTHDAY, and other BY* rules

### Must-Have Features

- [ ] MONTHLY/YEARLY Patterns - Complete support for monthly and yearly recurrence `L`
- [ ] BY* Rules Implementation - BYMONTHDAY, BYDAY, BYMONTH, BYWEEKNO support `XL`
- [ ] BYSETPOS Logic - Advanced occurrence selection with BYSETPOS `L`
- [ ] Complex Pattern Testing - Comprehensive test suite for advanced patterns `L`

### Should-Have Features

- [ ] WKST Support - Week start day configuration `M`
- [ ] Exception Handling - EXDATE and EXRULE support `L`

### Dependencies

- Phase 2 basic occurrence generation
- Comprehensive test coverage

## Phase 4: RFC 5545 Context & Integration (2 weeks)

**Goal:** Parse related RFC 5545 data and provide comprehensive integration features
**Success Criteria:** Handle VTODO, VEVENT, VCALENDAR context parsing

### Must-Have Features

- [ ] RFC 5545 Context Parser - Parse VTODO, VEVENT, VCALENDAR, DTSTART `M`
- [ ] Flexible Input Handling - Ignore irrelevant RFC 5545 fields gracefully `M`
- [ ] sabre/dav Compatibility Testing - Validate results against sabre/dav `L`
- [ ] Edge Case Handling - Comprehensive edge case testing and fixes `L`

### Should-Have Features

- [ ] Performance Benchmarking - Compare performance against sabre/dav `S`
- [ ] Documentation Examples - Comprehensive usage examples `M`

### Dependencies

- Phase 3 advanced RRULE features
- sabre/dav integration for testing

## Phase 5: Polish & Production Ready (1-2 weeks)

**Goal:** Production-ready library with comprehensive documentation and testing
**Success Criteria:** Ready for public release with full documentation

### Must-Have Features

- [ ] API Documentation - Complete PHPDoc coverage and generated docs `M`
- [ ] Usage Examples - README with comprehensive code samples `M`
- [ ] Error Message Improvement - Clear, actionable error messages `S`
- [ ] Final Testing - End-to-end testing with real-world RRULE patterns `M`

### Should-Have Features

- [ ] Performance Optimization - Final performance tuning `S`
- [ ] Release Preparation - Version tagging, changelog, Packagist submission `M`

### Dependencies

- Phase 4 completion
- Documentation review