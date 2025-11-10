# Quick Start - Running Validation Tests

## TL;DR

cd /home/jailuser/git
./tests/run-validation-tests.sh

Expected output: All tests passed successfully

## What Gets Tested

- PHP syntax (87 files)
- PHPDoc format (578 tags)
- Path conventions
- Completeness
- Documentation accuracy

## Why These Tests?

Changes are documentation-only (PHPDoc comments added).
Traditional unit tests do not apply.
Validation tests verify correctness, format, and completeness.

## Test Files

- tests/Validation/LegacyPhpDocValidationTest.php
- tests/Validation/DocumentationCompletenessTest.php
- tests/run-validation-tests.sh

## More Info

- See tests/README.md for details
- See TEST_SUITE_SUMMARY.md for comprehensive guide
- See COMPREHENSIVE_TEST_SUMMARY.md for results