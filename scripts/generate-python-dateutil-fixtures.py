#!/usr/bin/env python3
"""
Python-dateutil Fixture Generator

This script generates fixture files for testing RFC 5545 RRULE compatibility
with python-dateutil library. It reads input YAML files containing RRULE
specifications and generates corresponding output files with expected 
occurrences calculated using python-dateutil.

Supports both single test case per file and multiple test cases per file formats.

Usage:
    python generate-python-dateutil-fixtures.py [input_dir] [output_dir]
    
Default directories:
    input_dir: tests/fixtures/python-dateutil/input/
    output_dir: tests/fixtures/python-dateutil/generated/
"""

import os
import sys
import yaml
import hashlib
import datetime
from dateutil import rrule
from dateutil.parser import parse as parse_date
from dateutil.tz import gettz, UTC
from pathlib import Path
import argparse


class FixtureGenerator:
    """Generate python-dateutil fixtures from input YAML specifications."""
    
    SCRIPT_VERSION = "2.0.0"
    
    def __init__(self, input_dir: str, output_dir: str):
        self.input_dir = Path(input_dir)
        self.output_dir = Path(output_dir)
        
        # Ensure output directory exists
        self.output_dir.mkdir(parents=True, exist_ok=True)
    
    def calculate_input_hash(self, input_file_content: str) -> str:
        """Calculate SHA256 hash of entire input file content for integrity verification."""
        return hashlib.sha256(input_file_content.encode('utf-8')).hexdigest()
    
    def parse_rrule_frequency(self, freq_str: str) -> int:
        """Convert RRULE frequency string to dateutil constant."""
        freq_map = {
            'SECONDLY': rrule.SECONDLY,
            'MINUTELY': rrule.MINUTELY,
            'HOURLY': rrule.HOURLY,
            'DAILY': rrule.DAILY,
            'WEEKLY': rrule.WEEKLY,
            'MONTHLY': rrule.MONTHLY,
            'YEARLY': rrule.YEARLY
        }
        return freq_map.get(freq_str, rrule.DAILY)
    
    def parse_weekday(self, day_str: str) -> int:
        """Parse weekday string to dateutil constant."""
        weekday_map = {
            'MO': rrule.MO, 'TU': rrule.TU, 'WE': rrule.WE, 'TH': rrule.TH,
            'FR': rrule.FR, 'SA': rrule.SA, 'SU': rrule.SU
        }
        
        # Handle positional weekdays like "1MO", "-1FR"
        if len(day_str) > 2:
            # Extract position and weekday
            if day_str[0] == '-':
                pos = int(day_str[:-2])
                weekday_str = day_str[-2:]
            else:
                pos = int(day_str[:-2])
                weekday_str = day_str[-2:]
            
            base_weekday = weekday_map.get(weekday_str)
            return base_weekday(pos) if base_weekday else None
        else:
            return weekday_map.get(day_str)
    
    def parse_rrule_string(self, rrule_str: str, dtstart: datetime.datetime) -> rrule.rrule:
        """Parse RRULE string into python-dateutil rrule object."""
        parts = rrule_str.split(';')
        kwargs = {'dtstart': dtstart}
        
        for part in parts:
            if '=' in part:
                key, value = part.split('=', 1)
                
                if key == 'FREQ':
                    kwargs['freq'] = self.parse_rrule_frequency(value)
                elif key == 'INTERVAL':
                    kwargs['interval'] = int(value)
                elif key == 'COUNT':
                    kwargs['count'] = int(value)
                elif key == 'UNTIL':
                    # Parse UNTIL date
                    until_dt = parse_date(value)
                    kwargs['until'] = until_dt
                elif key == 'BYDAY':
                    # Parse BYDAY list
                    weekdays = []
                    for day in value.split(','):
                        parsed_day = self.parse_weekday(day.strip())
                        if parsed_day:
                            weekdays.append(parsed_day)
                    if weekdays:
                        kwargs['byweekday'] = weekdays
                elif key == 'BYMONTHDAY':
                    # Parse BYMONTHDAY list
                    monthdays = [int(d.strip()) for d in value.split(',')]
                    kwargs['bymonthday'] = monthdays
                elif key == 'BYMONTH':
                    # Parse BYMONTH list  
                    months = [int(m.strip()) for m in value.split(',')]
                    kwargs['bymonth'] = months
                elif key == 'BYSETPOS':
                    # Parse BYSETPOS list
                    setpos = [int(p.strip()) for p in value.split(',')]
                    kwargs['bysetpos'] = setpos
                elif key == 'BYWEEKNO':
                    # Parse BYWEEKNO list
                    weeknos = [int(w.strip()) for w in value.split(',')]
                    kwargs['byweekno'] = weeknos
                elif key == 'WKST':
                    # Parse week start day
                    wkst_day = self.parse_weekday(value)
                    if wkst_day:
                        kwargs['wkst'] = wkst_day
        
        return rrule.rrule(**kwargs)
    
    def generate_occurrences(self, input_data: dict) -> list:
        """Generate occurrences using python-dateutil."""
        rrule_str = input_data['rrule']
        dtstart_str = input_data['dtstart']
        timezone_str = input_data.get('timezone', 'UTC')
        range_data = input_data.get('range', {})
        
        # Parse timezone
        tz = gettz(timezone_str) if timezone_str != 'UTC' else UTC
        
        # Parse start date
        dtstart = parse_date(dtstart_str)
        if dtstart.tzinfo is None:
            dtstart = dtstart.replace(tzinfo=tz)
        
        # Create rrule object
        rule = self.parse_rrule_string(rrule_str, dtstart)
        
        # Generate occurrences
        occurrences = []
        
        if range_data:
            # Use range if specified
            range_start = parse_date(range_data['start'])
            range_end = parse_date(range_data['end'])
            
            if range_start.tzinfo is None:
                range_start = range_start.replace(tzinfo=tz)
            if range_end.tzinfo is None:
                range_end = range_end.replace(tzinfo=tz)
            
            for occurrence in rule:
                if occurrence > range_end:
                    break
                if occurrence >= range_start:
                    occurrences.append(occurrence)
        else:
            # Generate all occurrences (limited by COUNT or UNTIL in rrule)
            occurrences = list(rule)
        
        # Convert to ISO strings for serialization
        return [occ.isoformat() for occ in occurrences]
    
    def process_input_file(self, input_file: Path) -> bool:
        """Process a single input YAML file and generate corresponding output."""
        try:
            # Read input file content for hash calculation
            with open(input_file, 'r', encoding='utf-8') as f:
                input_content = f.read()
            
            # Parse input YAML
            input_data = yaml.safe_load(input_content)
            
            # Detect format: legacy single-test or new multi-test
            if self.is_legacy_format(input_data):
                return self.process_legacy_format(input_file, input_data, input_content)
            else:
                return self.process_multi_test_format(input_file, input_data, input_content)
                
        except Exception as e:
            print(f"Error processing {input_file}: {e}")
            return False
    
    def is_legacy_format(self, input_data: dict) -> bool:
        """Check if input uses legacy single-test format."""
        # Legacy format has direct rrule, dtstart fields, no test_cases
        return 'rrule' in input_data and 'test_cases' not in input_data
    
    def process_legacy_format(self, input_file: Path, input_data: dict, input_content: str) -> bool:
        """Process legacy single-test format for backward compatibility."""
        # Validate required fields
        required_fields = ['name', 'rrule', 'dtstart']
        for field in required_fields:
            if field not in input_data:
                print(f"Error: Missing required field '{field}' in {input_file}")
                return False
        
        # Generate occurrences
        try:
            occurrences = self.generate_occurrences(input_data)
        except Exception as e:
            print(f"Error generating occurrences for {input_file}: {e}")
            return False
        
        # Create output data in legacy format
        output_data = {
            'metadata': {
                'input_hash': self.calculate_input_hash(input_content),
                'python_dateutil_version': self.get_dateutil_version(),
                'script_version': self.SCRIPT_VERSION
            },
            'input': input_data,
            'expected_occurrences': occurrences
        }
        
        # Write output YAML
        output_file = self.output_dir / input_file.name
        with open(output_file, 'w', encoding='utf-8') as f:
            yaml.dump(output_data, f, default_flow_style=False, allow_unicode=True)
        
        print(f"Generated (legacy): {output_file} ({len(occurrences)} occurrences)")
        return True
    
    def process_multi_test_format(self, input_file: Path, input_data: dict, input_content: str) -> bool:
        """Process new multi-test format."""
        # Validate structure
        if 'metadata' not in input_data:
            print(f"Error: Missing 'metadata' section in {input_file}")
            return False
        
        if 'test_cases' not in input_data:
            print(f"Error: Missing 'test_cases' section in {input_file}")
            return False
        
        metadata = input_data['metadata']
        test_cases = input_data['test_cases']
        
        # Validate metadata
        required_metadata = ['name', 'category']
        for field in required_metadata:
            if field not in metadata:
                print(f"Error: Missing required metadata field '{field}' in {input_file}")
                return False
        
        if not isinstance(test_cases, list) or len(test_cases) == 0:
            print(f"Error: 'test_cases' must be a non-empty list in {input_file}")
            return False
        
        # Process each test case
        output_test_cases = []
        total_occurrences = 0
        
        for i, test_case in enumerate(test_cases):
            # Validate required fields
            required_fields = ['rrule', 'dtstart']
            for field in required_fields:
                if field not in test_case:
                    print(f"Error: Missing required field '{field}' in test case {i} of {input_file}")
                    return False
            
            # Generate occurrences
            try:
                occurrences = self.generate_occurrences(test_case)
                total_occurrences += len(occurrences)
            except Exception as e:
                print(f"Error generating occurrences for test case {i} in {input_file}: {e}")
                return False
            
            # Create output test case
            output_test_case = {
                'input': test_case,
                'expected_occurrences': occurrences
            }
            output_test_cases.append(output_test_case)
        
        # Create output data
        output_data = {
            'metadata': {
                'name': metadata['name'],
                'description': metadata.get('description', ''),
                'category': metadata['category'],
                'input_hash': self.calculate_input_hash(input_content),
                'python_dateutil_version': self.get_dateutil_version(),
                'script_version': self.SCRIPT_VERSION
            },
            'test_cases': output_test_cases
        }
        
        # Write output YAML
        output_file = self.output_dir / input_file.name
        with open(output_file, 'w', encoding='utf-8') as f:
            yaml.dump(output_data, f, default_flow_style=False, allow_unicode=True)
        
        print(f"Generated: {output_file} ({len(test_cases)} test cases, {total_occurrences} total occurrences)")
        return True
    
    def get_dateutil_version(self) -> str:
        """Get python-dateutil version."""
        try:
            import dateutil
            return dateutil.__version__
        except AttributeError:
            return "unknown"
    
    def generate_all_fixtures(self) -> int:
        """Process all input YAML files and generate corresponding output files."""
        if not self.input_dir.exists():
            print(f"Error: Input directory {self.input_dir} does not exist")
            return 1
        
        # Find all YAML files in input directory
        input_files = list(self.input_dir.glob('*.yaml')) + list(self.input_dir.glob('*.yml'))
        
        if not input_files:
            print(f"No YAML files found in {self.input_dir}")
            return 0
        
        success_count = 0
        for input_file in input_files:
            if self.process_input_file(input_file):
                success_count += 1
        
        print(f"\nProcessed {success_count}/{len(input_files)} files successfully")
        return 0 if success_count == len(input_files) else 1


def main():
    """Main entry point."""
    parser = argparse.ArgumentParser(description='Generate python-dateutil fixtures from YAML input')
    parser.add_argument('input_dir', nargs='?', 
                       default='tests/fixtures/python-dateutil/input',
                       help='Input directory containing YAML files (default: tests/fixtures/python-dateutil/input)')
    parser.add_argument('output_dir', nargs='?',
                       default='tests/fixtures/python-dateutil/generated',
                       help='Output directory for generated fixtures (default: tests/fixtures/python-dateutil/generated)')
    
    args = parser.parse_args()
    
    generator = FixtureGenerator(args.input_dir, args.output_dir)
    return generator.generate_all_fixtures()


if __name__ == '__main__':
    sys.exit(main())