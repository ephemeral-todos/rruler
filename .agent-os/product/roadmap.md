# Product Roadmap

> Last Updated: 2025-08-02
> Version: 1.0.0
> Status: In Progress

## Phase 1: Foundation & Core Parser (COMPLETE)

**Goal:** Establish project foundation with basic RRULE parsing capability
**Success Criteria:** Can parse simple DAILY, WEEKLY, MONTHLY, YEARLY patterns

### Features

- [x] Project Setup - Composer package, PHPUnit, PHPStan, PHP-CS-Fixer configuration `M`
- [x] AST Parser Foundation - Basic tokenizer and AST node structure for RRULE parsing `L`
- [x] Core RRULE Parser - Parse FREQ, INTERVAL, COUNT, UNTIL basic parameters `L`
- [x] Basic Validation - Input validation and error handling for malformed RRULE strings `M`
- [x] Unit Test Foundation - Test framework setup with initial parser tests `M`
- [x] Development Tools - Justfile/Makefile with common tasks (test, lint, fix) `S`

### Dependencies

- Composer package structure
- PHPUnit testing framework

## Phase 2: Basic Occurrence Generation (COMPLETE)

**Goal:** Generate occurrence dates for simple recurrence patterns
**Success Criteria:** Calculate occurrences for basic FREQ patterns with COUNT/UNTIL

### Features

- [x] DateTime Handling - Robust DateTime/DateTimeImmutable integration `M`
- [x] Basic Occurrence Calculator - Generate occurrences for DAILY, WEEKLY patterns `L`
- [x] Count/Until Logic - Implement COUNT and UNTIL termination conditions `M`
- [x] Date Range Filtering - Generate occurrences between specified date ranges `M`
- [x] Occurrence Validation - Check if specific DateTime is a valid occurrence `M`
- [x] Performance Optimization - Generator patterns for large occurrence sets `S`
- [x] Memory Efficiency - Lazy evaluation implementation `S`

### Dependencies

- Phase 1 RRULE parser
- Basic validation framework

## Phase 3: Advanced RRULE Features (IN PROGRESS)

**Goal:** Support complex RRULE patterns and advanced RFC 5545 features
**Success Criteria:** Handle BYSETPOS, BYWEEKNO, BYMONTHDAY, and other BY* rules

### Features

- [x] BYDAY Implementation - Complete support for weekday patterns including positional prefixes `L`
- [x] MONTHLY/YEARLY Patterns - Complete support for monthly and yearly recurrence `L`
- [x] BYMONTHDAY Support - Days of month selection for monthly/yearly patterns `M`
- [ ] BYMONTH Implementation - Month selection for yearly patterns `M`
- [ ] BYWEEKNO Support - Week number selection for yearly patterns `L`
- [ ] BYSETPOS Logic - Advanced occurrence selection with BYSETPOS `L`
- [ ] Complex Pattern Testing - Comprehensive test suite for advanced patterns `L`

### Should-Have Features

- [ ] WKST Support - Week start day configuration `M`
- [ ] Exception Handling - EXDATE and EXRULE support `L`

### Dependencies

- Phase 2 basic occurrence generation
- Comprehensive test coverage

## Phase 4: RFC 5545 Context & Integration (PLANNED)

**Goal:** Parse related RFC 5545 data and provide comprehensive integration features
**Success Criteria:** Handle VTODO, VEVENT, VCALENDAR context parsing

### Features

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

## Phase 5: Polish & Production Ready (PLANNED)

**Goal:** Production-ready library with comprehensive documentation and testing
**Success Criteria:** Ready for public release with full documentation

### Features

- [ ] CI Pipeline - GitHub Actions for testing across PHP versions `M`
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

## Current Status

**Active Phase:** Phase 3 (Advanced RRULE Features)
**Next Priority:** BYMONTH implementation for yearly patterns
**Completion:** ~70% of planned features implemented