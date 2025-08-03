# Technical Stack

> Last Updated: 2025-08-02
> Version: 1.0.0

## Core Technologies

### Application Framework
- **Framework:** Standalone PHP Library
- **Version:** PHP ^8.3
- **Language:** PHP 8.3+

## Library Architecture

### Parser Strategy
- **Approach:** AST (Abstract Syntax Tree) based parsing
- **Fallback:** Regex-based parsing if AST proves too complex
- **Input Format:** RFC 5545 RRULE strings

### Package Manager
- **Package Manager:** Composer
- **PHP Version:** 8.3+
- **Distribution:** Packagist

## Development Stack

### Testing Framework
- **Framework:** PHPUnit
- **Version:** Latest stable for PHP 8.3+
- **Coverage:** Xdebug or PCOV

### Code Quality
- **Static Analysis:** PHPStan (level max)
- **Code Style:** PER-CS and Symfony coding standards
- **Formatter:** PHP-CS-Fixer with PER-CS and Symfony rules

### Development Tools
- **Dependency Management:** Composer
- **Task Runner:** Just (justfile) or Make (Makefile)
- **Git Hooks:** Pre-commit hooks for quality checks

## External Dependencies

### Production Dependencies
- **Minimal:** Keep production dependencies to absolute minimum
- **DateTime:** Built-in PHP DateTime/DateTimeImmutable classes
- **Validation:** Custom validation (no external dependencies preferred)

### Development Dependencies
- **PHPUnit:** Testing framework
- **PHPStan:** Static analysis
- **PHP-CS-Fixer:** Code formatting
- **sabre/vobject:** Testing validation (dev only, for comparing results)

## Infrastructure

### Package Distribution
- **Platform:** Packagist (official Composer repository)
- **Versioning:** Semantic versioning (SemVer)
- **License:** MIT

### CI/CD Pipeline
- **Platform:** GitHub Actions
- **Trigger:** Push to main branch and PRs
- **Tests:** Multiple PHP versions (8.3, 8.4)
- **Quality Gates:** PHPStan, PHP-CS-Fixer, PHPUnit coverage

### Documentation
- **API Docs:** Generated from PHPDoc comments
- **Usage Examples:** README.md with code samples
- **RFC Reference:** Link to RFC 5545 specification

## Performance Considerations

### Memory Usage
- **Target:** Minimal memory footprint
- **Approach:** Lazy evaluation where possible
- **Optimization:** Generator patterns for large occurrence sets

### Execution Speed
- **Priority:** Developer experience (readability) over performance optimizations
- **Focus:** Accurate parsing and maintainable code
- **Caching:** In-memory caching of parsed RRULE objects
- **Benchmarking:** Performance tests against sabre/dav