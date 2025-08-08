# Spec Requirements Document

> Spec: README Usage Examples
> Created: 2025-08-08
> Status: Planning

## Overview

Create a focused README.md that demonstrates Rruler's value proposition as a standalone RFC 5545 RRULE parser with practical examples that showcase the library's positioning against heavier alternatives like sabre/dav. The README should enable developers to quickly evaluate the library and understand its core capabilities through clear, actionable code examples.

## User Stories

### Developer Discovery Story

As a PHP developer searching for RRULE parsing solutions, I want to quickly understand what Rruler offers and how it differs from existing solutions, so that I can determine if it fits my project needs without extensive research.

**Detailed Workflow:** Developer finds the repository through search or recommendations, scans the README to understand the library's purpose, sees positioning against sabre/dav, and evaluates whether the focused scope matches their requirements.

### Integration Evaluation Story

As a developer building a calendar or TODO application, I want to see practical code examples showing common RRULE patterns, so that I can understand the API design and implementation complexity before adding it as a dependency.

**Detailed Workflow:** Developer reviews installation steps, examines 3-4 realistic usage examples covering typical recurrence patterns, evaluates the API surface area, and assesses integration effort required.

### Quick Implementation Story

As a developer ready to integrate RRULE parsing, I want clear installation instructions and copy-pasteable examples, so that I can get basic functionality working within minutes of adding the dependency.

**Detailed Workflow:** Developer follows installation steps, copies a relevant usage example, adapts it to their use case, and successfully parses their first RRULE string with generated occurrences.

## Spec Scope

1. **Value Proposition Statement** - Clear explanation of Rruler's focused scope vs full WebDAV/CalDAV ecosystems
2. **Installation Instructions** - Composer installation with PHP version requirements
3. **Common Use Case Examples** - 3-4 practical scenarios covering daily/weekly/monthly patterns with real-world context
4. **Basic API Demonstration** - Core parsing and occurrence generation workflow with error handling
5. **Positioning Statement** - Comparison with sabre/dav highlighting when to choose Rruler

## Out of Scope

- Comprehensive API documentation (belongs in generated docs)
- Advanced RRULE features showcase (BYSETPOS, BYWEEKNO examples)
- Detailed RFC 5545 compliance information
- Performance benchmarking data
- Complete feature matrix or comparison table

## Expected Deliverable

A README.md file that enables developers to:
1. **Evaluate** the library's fit for their project within 2-3 minutes of reading
2. **Install** and get basic functionality working within 5 minutes
3. **Understand** the API design through practical, copy-pasteable examples
4. **Position** the library correctly relative to their needs and existing solutions

## Spec Documentation

- Tasks: @.agent-os/specs/2025-08-08-readme-usage-examples/tasks.md
- Technical Specification: @.agent-os/specs/2025-08-08-readme-usage-examples/sub-specs/technical-spec.md