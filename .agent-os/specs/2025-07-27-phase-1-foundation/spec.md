# Spec Requirements Document

> Spec: Phase 1 Foundation & Core Parser
> Created: 2025-07-27
> Status: **COMPLETED** ✅
> Completed: 2025-07-28

## Overview

Establish the project foundation with complete development environment setup and implement basic RRULE parsing capability for simple recurrence patterns (DAILY, WEEKLY, MONTHLY, YEARLY).

## User Stories

### Developer Setting Up Project

As a PHP developer, I want to clone the repository and run tests immediately, so that I can contribute to the project without configuration overhead.

The developer should be able to run `composer install`, `just test` (or equivalent), and see a working test suite with basic RRULE parsing functionality.

### Application Developer Using Basic RRULE

As an application developer, I want to parse simple RRULE strings like "FREQ=DAILY;INTERVAL=2;COUNT=10", so that I can validate and work with basic recurring patterns.

The parser should handle the core RRULE parameters (FREQ, INTERVAL, COUNT, UNTIL) and provide clear validation errors for malformed input.

## Spec Scope

1. **Complete Development Environment** - Composer, PHPUnit, PHPStan, PHP-CS-Fixer with PER-CS/Symfony standards
2. **AST Parser Foundation** - Tokenizer and basic AST node structure for extensible RRULE parsing
3. **Core RRULE Parser** - Parse FREQ, INTERVAL, COUNT, UNTIL parameters with validation
4. **Unit Test Framework** - Comprehensive test coverage for all parsing functionality
5. **Development Automation** - Justfile with test, lint, fix, and analysis commands

## Out of Scope

- Complex BY* rules (BYMONTHDAY, BYDAY, etc.) - reserved for Phase 3
- Occurrence generation/calculation - reserved for Phase 2  
- RFC 5545 context parsing (VEVENT, VTODO) - reserved for Phase 4
- Performance optimization - reserved for later phases

## Expected Deliverable

1. Developer can run `composer install && just test` and see passing tests
2. Parser correctly handles basic RRULE strings: `FREQ=DAILY`, `FREQ=WEEKLY;INTERVAL=2`, `FREQ=MONTHLY;COUNT=5`
3. Clear validation errors for malformed RRULE input with specific error messages

## Spec Documentation

- Tasks: @.agent-os/specs/2025-07-27-phase-1-foundation/tasks.md
- Technical Specification: @.agent-os/specs/2025-07-27-phase-1-foundation/sub-specs/technical-spec.md
- Tests Specification: @.agent-os/specs/2025-07-27-phase-1-foundation/sub-specs/tests.md