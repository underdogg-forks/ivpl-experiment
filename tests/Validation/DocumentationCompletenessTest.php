<?php

/**
 * Test to validate the LEGACY_PHPDOC_SUMMARY.md documentation
 * matches the actual changes made to the codebase
 */
class DocumentationCompletenessTest
{
    private array $errors = [];

    public function runAll(): bool
    {
        echo "=== Documentation Completeness Test ===\n\n";
        
        $allPassed = true;
        
        $allPassed = $this->testSummaryFileExists() && $allPassed;
        $allPassed = $this->testStatisticsMatch() && $allPassed;
        $allPassed = $this->testDocumentationStructure() && $allPassed;
        
        $this->printSummary();
        
        return $allPassed;
    }

    /**
     * Test: Verify LEGACY_PHPDOC_SUMMARY.md exists
     */
    private function testSummaryFileExists(): bool
    {
        echo "Test: Summary File Exists\n";
        echo str_repeat("-", 50) . "\n";
        
        if (file_exists('LEGACY_PHPDOC_SUMMARY.md')) {
            echo "✓ LEGACY_PHPDOC_SUMMARY.md exists\n\n";
            return true;
        }
        echo "✗ LEGACY_PHPDOC_SUMMARY.md not found\n\n";
        $this->errors[] = "Missing documentation file";
        return false;
    }

    /**
     * Test: Verify statistics in summary match actual changes
     */
    private function testStatisticsMatch(): bool
    {
        echo "Test: Statistics Accuracy\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!file_exists('LEGACY_PHPDOC_SUMMARY.md')) {
            echo "✗ Cannot verify statistics - file missing\n\n";
            return false;
        }
        
        $summary = file_get_contents('LEGACY_PHPDOC_SUMMARY.md');
        
        // Count actual changed files
        $output = [];
        exec('git diff --name-only --diff-filter=AM develop..HEAD | grep "\.php$" | wc -l', $output);
        $actualFiles = (int)trim($output[0]);
        
        // Extract claimed file count from summary
        if (!preg_match('/Total Files Modified.*?(\d+)\s+files/i', $summary, $match)) {
            echo "✗ Could not parse file count from summary\n\n";
            $this->errors[] = "Cannot parse statistics from summary";
            return false;
        }
        $claimedFiles = (int)$match[1];
        
        if ($actualFiles === $claimedFiles) {
            echo "✓ File count matches: $actualFiles files\n\n";
            return true;
        }
        echo "✗ File count mismatch: claimed $claimedFiles, actual $actualFiles\n\n";
        $this->errors[] = "File count mismatch in documentation";
        return false;
    }

    /**
     * Test: Verify documentation has required sections
     */
    private function testDocumentationStructure(): bool
    {
        echo "Test: Documentation Structure\n";
        echo str_repeat("-", 50) . "\n";
        
        if (!file_exists('LEGACY_PHPDOC_SUMMARY.md')) {
            echo "✗ Cannot verify structure - file missing\n\n";
            return false;
        }
        
        $summary = file_get_contents('LEGACY_PHPDOC_SUMMARY.md');
        $requiredSections = [
            'Task Completed',
            'Statistics',
            'Files Documented',
            'Documentation Format',
            'Benefits',
        ];
        
        $missingSections = [];
        foreach ($requiredSections as $section) {
            if (stripos($summary, $section) === false) {
                $missingSections[] = $section;
            }
        }
        
        if (empty($missingSections)) {
            echo "✓ All required sections present\n\n";
            return true;
        }
        echo "✗ Missing sections: " . implode(', ', $missingSections) . "\n\n";
        $this->errors = array_merge($this->errors, $missingSections);
        return false;
    }

    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Documentation Test Summary\n";
        echo str_repeat("=", 50) . "\n";
        echo "Errors: " . count($this->errors) . "\n";
        
        if (empty($this->errors)) {
            echo "\n✓ All documentation tests passed!\n";
            return;
        }
        echo "\n✗ Documentation validation failed\n";
    }
}

// Run the tests
$tester = new DocumentationCompletenessTest();
$success = $tester->runAll();
exit($success ? 0 : 1);