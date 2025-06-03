# SOL25 Language Interpreter

A PHP interpreter for the SOL25 language, implementing XML source code parsing and execution with error handling and I/O management.

## Project Overview
This project implements an interpreter for SOL25 (a simple Smalltalk-like programming language) as part of the IPP course at BUT FIT. The interpreter reads XML-formatted source code and executes it with proper error handling and return codes.  

The `/core/` was provided by the teachers. My original work is present in `/student/`, implementing the interpret.

## Usage
```bash
php interpret.php [options...]
```

### Options
- `--help` - Display help message and exit
- `--source=<file>` - XML source code file
- `--input=<file>` - Input data file

**Note**: At least one of `--source` or `--input` must be specified. Unspecified options use STDIN.

### Examples
```bash
# Execute from XML file with input from stdin
php interpret.php --source=program.xml

# Execute from stdin with input file
php interpret.php --input=data.txt

# Execute with both files specified
php interpret.php --source=program.xml --input=data.txt
```

### Tests
Also implemented tests myself for this project. Publicly available at: https://github.com/Kubikuli/IPP_proj2-tests

**Total points: 16/13 points** (13+3 bonus)
