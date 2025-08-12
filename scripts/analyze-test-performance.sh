#!/bin/bash

# PHPUnit Performance Analysis Script
# Analyzes both individual test execution times and aggregate performance by test name
# Usage: ./analyze-test-performance.sh [options]

set -e

# Configuration
TIMING_LOG="test_timing_analysis.log"
TEMP_DIR="/tmp/phpunit_performance_$$"
TOP_COUNT=20

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# Help function
show_help() {
    echo "PHPUnit Performance Analysis Tool"
    echo ""
    echo "Usage: $0 [options]"
    echo ""
    echo "Options:"
    echo "  -h, --help          Show this help message"
    echo "  -t, --top N         Show top N tests (default: 20)"
    echo "  -q, --quiet         Suppress PHPUnit output during test run"
    echo "  -k, --keep-logs     Keep timing log files after analysis"
    echo "  -a, --aggregate-only Show only aggregate analysis"
    echo "  -i, --individual-only Show only individual test analysis"
    echo "  --no-run           Analyze existing log file (skip test execution)"
    echo ""
    echo "Examples:"
    echo "  $0                 # Run full analysis"
    echo "  $0 -t 10           # Show top 10 tests"
    echo "  $0 -q -a           # Quiet mode, aggregate only"
    echo "  $0 --no-run        # Analyze existing ${TIMING_LOG}"
}

# Parse command line arguments
RUN_TESTS=true
SHOW_INDIVIDUAL=true
SHOW_AGGREGATE=true
QUIET_MODE=false
KEEP_LOGS=false

while [[ $# -gt 0 ]]; do
    case $1 in
        -h|--help)
            show_help
            exit 0
            ;;
        -t|--top)
            TOP_COUNT="$2"
            shift 2
            ;;
        -q|--quiet)
            QUIET_MODE=true
            shift
            ;;
        -k|--keep-logs)
            KEEP_LOGS=true
            shift
            ;;
        -a|--aggregate-only)
            SHOW_INDIVIDUAL=false
            shift
            ;;
        -i|--individual-only)
            SHOW_AGGREGATE=false
            shift
            ;;
        --no-run)
            RUN_TESTS=false
            shift
            ;;
        *)
            echo "Unknown option: $1" >&2
            show_help
            exit 1
            ;;
    esac
done

# Create temp directory
mkdir -p "$TEMP_DIR"

# Cleanup function
cleanup() {
    if [[ "$KEEP_LOGS" == "false" ]]; then
        rm -f "$TIMING_LOG"
    fi
    rm -rf "$TEMP_DIR"
}
trap cleanup EXIT

# Function to print colored headers
print_header() {
    echo -e "${BOLD}${BLUE}$1${NC}"
    echo -e "${BLUE}$(printf '%*s' ${#1} '' | tr ' ' '=')${NC}"
}

# Function to print section headers
print_section() {
    echo ""
    echo -e "${BOLD}${YELLOW}$1${NC}"
    echo -e "${YELLOW}$(printf '%*s' ${#1} '' | tr ' ' '-')${NC}"
}

# Function to run PHPUnit with timing
run_phpunit_with_timing() {
    print_header "Running PHPUnit Performance Analysis"
    echo "Executing tests with detailed timing logging..."
    
    # Always use vendor/bin/phpunit directly for better control over arguments
    if [[ "$QUIET_MODE" == "true" ]]; then
        vendor/bin/phpunit --log-events-verbose-text "$TIMING_LOG" > /dev/null 2>&1
    else
        vendor/bin/phpunit --log-events-verbose-text "$TIMING_LOG"
    fi
    
    echo -e "${GREEN}✓ Test execution complete${NC}"
    echo "Timing data captured in: $TIMING_LOG"
}

# Function to analyze individual test performance
analyze_individual_performance() {
    print_section "Individual Test Performance Analysis"
    
    echo "Extracting individual test execution times..."
    
    # Extract individual test times
    grep "Test Finished" "$TIMING_LOG" | \
        sed 's/.*\/ 00:00:00\.\(0*\)\([0-9]*\)].*Test Finished (\(.*\))/\2 \3/' | \
        sort -nr > "$TEMP_DIR/individual_times.txt"
    
    local total_tests=$(wc -l < "$TEMP_DIR/individual_times.txt")
    local slowest=$(head -1 "$TEMP_DIR/individual_times.txt" | awk '{print $1}')
    local median_line=$((total_tests / 2))
    local median=$(sed -n "${median_line}p" "$TEMP_DIR/individual_times.txt" | awk '{print $1}')
    local percentile_90=$((total_tests / 10))
    local fast_90=$(head -n "$percentile_90" "$TEMP_DIR/individual_times.txt" | tail -1 | awk '{print $1}')
    
    echo ""
    echo -e "${BOLD}Performance Statistics:${NC}"
    echo "  Total Tests: $total_tests"
    echo "  Slowest Test: $(printf "%0.3f" $(echo "scale=3; $slowest/1000000" | bc))ms"
    echo "  Median Time: $(printf "%0.3f" $(echo "scale=3; $median/1000000" | bc))ms"  
    echo "  90th Percentile: $(printf "%0.3f" $(echo "scale=3; $fast_90/1000000" | bc))ms"
    
    echo ""
    echo -e "${BOLD}Top $TOP_COUNT Slowest Individual Tests:${NC}"
    printf "%-8s %-80s\n" "TIME(ms)" "TEST_NAME"
    printf "%-8s %-80s\n" "========" "$(printf '=%.0s' $(seq 1 80))"
    
    head -n "$TOP_COUNT" "$TEMP_DIR/individual_times.txt" | while read -r time test_name; do
        time_ms=$(printf "%0.3f" $(echo "scale=3; $time/1000000" | bc))
        printf "%-8s %s\n" "$time_ms" "$test_name"
    done
}

# Function to analyze aggregate test performance  
analyze_aggregate_performance() {
    print_section "Aggregate Test Performance Analysis (By Test Name)"
    
    echo "Calculating aggregate timing by test name..."
    
    # Calculate aggregate times
    grep "Test Finished" "$TIMING_LOG" | \
        sed 's/.*\/ 00:00:00\.\(0*\)\([0-9]*\)].*Test Finished (\(.*\))/\2 \3/' | \
        awk '{
            # Extract test name without data provider parameters
            test_name = $2
            gsub(/#.*$/, "", test_name)  # Remove everything after # (data provider info)
            
            # Convert timing to microseconds for easier math
            timing = $1
            
            # Accumulate timing and count for each test
            total_time[test_name] += timing
            count[test_name]++
        } END {
            # Output results
            for (test in total_time) {
                avg = total_time[test] / count[test]
                printf "%d %d %d %s\n", total_time[test], count[test], avg, test
            }
        }' | sort -nr > "$TEMP_DIR/aggregate_times.txt"
    
    local total_unique_tests=$(wc -l < "$TEMP_DIR/aggregate_times.txt")
    local highest_total=$(head -1 "$TEMP_DIR/aggregate_times.txt" | awk '{print $1}')
    local highest_runs=$(head -1 "$TEMP_DIR/aggregate_times.txt" | awk '{print $2}')
    
    echo ""
    echo -e "${BOLD}Aggregate Statistics:${NC}"
    echo "  Unique Test Methods: $total_unique_tests"
    echo "  Highest Total Time: $(printf "%0.3f" $(echo "scale=3; $highest_total/1000000" | bc))ms"
    echo "  Most Executions: $highest_runs runs"
    
    echo ""
    echo -e "${BOLD}Top $TOP_COUNT Time-Consuming Tests (Aggregate):${NC}"
    printf "%-10s %-6s %-8s %s\n" "TOTAL(ms)" "RUNS" "AVG(ms)" "TEST_NAME"
    printf "%-10s %-6s %-8s %s\n" "=========" "====" "=======" "$(printf '=%.0s' $(seq 1 60))"
    
    head -n "$TOP_COUNT" "$TEMP_DIR/aggregate_times.txt" | while read -r total_time runs avg_time test_name; do
        total_ms=$(printf "%0.3f" $(echo "scale=3; $total_time/1000000" | bc))
        avg_ms=$(printf "%0.3f" $(echo "scale=3; $avg_time/1000000" | bc))
        printf "%-10s %-6s %-8s %s\n" "$total_ms" "$runs" "$avg_ms" "$test_name"
    done
}

# Function to show summary and insights
show_summary() {
    print_section "Performance Insights"
    
    echo "Key Observations:"
    echo ""
    
    if [[ "$SHOW_INDIVIDUAL" == "true" && "$SHOW_AGGREGATE" == "true" ]]; then
        # Compare individual vs aggregate top tests
        local individual_top=$(head -1 "$TEMP_DIR/individual_times.txt" | awk '{print $2}' | sed 's/#.*//')
        local aggregate_top=$(head -1 "$TEMP_DIR/aggregate_times.txt" | awk '{print $4}')
        
        if [[ "$individual_top" != "$aggregate_top" ]]; then
            echo -e "${YELLOW}• Different tests dominate individual vs aggregate performance${NC}"
            echo "  - Slowest individual test: $(basename "$individual_top")"
            echo "  - Highest aggregate consumer: $(basename "$aggregate_top")"
        fi
        
        # Check for high-volume tests
        local high_volume=$(head -5 "$TEMP_DIR/aggregate_times.txt" | awk '$2 > 20 {print $2, $4}' | head -1)
        if [[ -n "$high_volume" ]]; then
            local runs=$(echo "$high_volume" | awk '{print $1}')
            local test=$(echo "$high_volume" | awk '{print $2}')
            echo -e "${YELLOW}• High-volume test detected: $(basename "$test") ($runs runs)${NC}"
            echo "  Consider consolidating data provider cases for better performance"
        fi
    fi
    
    echo -e "${GREEN}• Analysis complete - use results to identify optimization opportunities${NC}"
    
    if [[ "$KEEP_LOGS" == "true" ]]; then
        echo ""
        echo "Timing log preserved: $TIMING_LOG"
    fi
}

# Main execution
main() {
    print_header "PHPUnit Test Performance Analyzer"
    
    # Check if we should run tests
    if [[ "$RUN_TESTS" == "true" ]]; then
        if [[ ! -f "composer.json" && ! -f "vendor/bin/phpunit" ]]; then
            echo -e "${RED}Error: No composer.json or vendor/bin/phpunit found${NC}" >&2
            echo "Please run this script from a PHP project root directory" >&2
            exit 1
        fi
        run_phpunit_with_timing
    else
        if [[ ! -f "$TIMING_LOG" ]]; then
            echo -e "${RED}Error: Timing log file '$TIMING_LOG' not found${NC}" >&2
            echo "Run without --no-run flag to generate timing data first" >&2
            exit 1
        fi
        echo "Using existing timing log: $TIMING_LOG"
    fi
    
    # Verify log file has test data
    if ! grep -q "Test Finished" "$TIMING_LOG"; then
        echo -e "${RED}Error: No test timing data found in log file${NC}" >&2
        exit 1
    fi
    
    # Run analysis based on options
    if [[ "$SHOW_INDIVIDUAL" == "true" ]]; then
        analyze_individual_performance
    fi
    
    if [[ "$SHOW_AGGREGATE" == "true" ]]; then
        analyze_aggregate_performance  
    fi
    
    show_summary
}

# Check for bc command
if ! command -v bc &> /dev/null; then
    echo -e "${RED}Error: 'bc' command not found${NC}" >&2
    echo "Please install bc: brew install bc (macOS) or apt-get install bc (Linux)" >&2
    exit 1
fi

# Run main function
main "$@"
