#!/bin/bash
set -euo pipefail

echo "========================================"
echo "Legacy PHPDoc Validation Test Suite"
echo "========================================"
echo ""

cd "$(dirname "$0")/.."

# Track overall success
OVERALL_SUCCESS=0

# Run PHPDoc validation tests
echo "Running PHPDoc validation tests..."
if php tests/Validation/LegacyPhpDocValidationTest.php; then
    echo "✓ PHPDoc validation passed"
else
    echo "✗ PHPDoc validation failed"
    OVERALL_SUCCESS=1
fi

echo ""
echo "========================================"
echo ""

# Run documentation completeness tests
echo "Running documentation completeness tests..."
if php tests/Validation/DocumentationCompletenessTest.php; then
    echo "✓ Documentation completeness passed"
else
    echo "✗ Documentation completeness failed"
    OVERALL_SUCCESS=1
fi

echo ""
echo "========================================"
echo "Test Suite Complete"
echo "========================================"

if [ $OVERALL_SUCCESS -eq 0 ]; then
    echo "✓ All tests passed successfully!"
    exit 0
else
    echo "✗ Some tests failed"
    exit 1
fi