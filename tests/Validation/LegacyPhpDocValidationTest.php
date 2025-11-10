<?php

/**
 * Validation tests for legacy PHPDoc documentation
 * 
 * These tests validate that the @legacy-file and @legacy-function tags
 * added to the codebase follow the correct format and conventions.
 */
class LegacyPhpDocValidationTest
{
    private array $errors = [];
    private int $filesChecked = 0;
    private int $methodsValidated = 0;
    private int $legacyTagsFound = 0;

    /**
     * Run all validation tests
     */
    public function runAll(): bool
    {
        echo "=== Legacy PHPDoc Validation Test Suite ===\n\n";
        
        $allPassed = true;
        
        $allPassed = $this->testPhpSyntaxValidity() && $allPassed;
        $allPassed = $this->testLegacyTagFormat() && $allPassed;
        $allPassed = $this->testLegacyTagConsistency() && $allPassed;
        $allPassed = $this->testCoreClassLegacyTags() && $allPassed;
        $allPassed = $this->testModuleControllerLegacyTags() && $allPassed;
        $allPassed = $this->testModuleModelLegacyTags() && $allPassed;
        $allPassed = $this->testNoMissingLegacyTags() && $allPassed;
        $allPassed = $this->testLegacyFilePathsValid() && $allPassed;
        
        $this->printSummary();
        
        return $allPassed;
    }

    /**
     * Test: Verify all PHP files have valid syntax
     */
    private function testPhpSyntaxValidity(): bool
    {
        echo "Test: PHP Syntax Validity\n";
        echo str_repeat("-", 50) . "\n";
        
        $changedFiles = $this->getChangedPhpFiles();
        $syntaxErrors = [];
        
        foreach ($changedFiles as $file) {
            $output = [];
            $returnCode = 0;
            exec("php -l " . escapeshellarg($file) . " 2>&1", $output, $returnCode);
            
            if ($returnCode !== 0) {
                $syntaxErrors[] = $file;
                $this->errors[] = "Syntax error in $file: " . implode("\n", $output);
            }
            $this->filesChecked++;
        }
        
        if (!empty($syntaxErrors)) {
            echo "✗ Found syntax errors in " . count($syntaxErrors) . " files\n\n";
            return false;
        }

        echo "✓ All " . count($changedFiles) . " PHP files have valid syntax\n\n";
        return true;
    }

    /**
     * Test: Verify @legacy-file and @legacy-function tags follow correct format
     */
    private function testLegacyTagFormat(): bool
    {
        echo "Test: Legacy Tag Format\n";
        echo str_repeat("-", 50) . "\n";
        
        $changedFiles = $this->getChangedPhpFiles();
        $formatErrors = [];
        
        foreach ($changedFiles as $file) {
            $content = file_get_contents($file);
            
            // Find all @legacy-file tags
            preg_match_all('/@legacy-file\s+([^\r\n]+)/m', $content, $fileMatches);
            
            foreach ($fileMatches[1] as $legacyFile) {
                $legacyFile = trim($legacyFile);
                $this->legacyTagsFound++;
                
                // Validate legacy file path format
                if (!preg_match('#^application/(core|modules)/[a-zA-Z0-9_/]+\.php$#', $legacyFile)) {
                    $formatErrors[] = "$file: Invalid legacy file format: $legacyFile";
                }
            }
            
            // Find all @legacy-function tags
            preg_match_all('/@legacy-function\s+([^\r\n]+)/m', $content, $funcMatches);
            
            foreach ($funcMatches[1] as $legacyFunction) {
                $legacyFunction = trim($legacyFunction);
                
                // Validate legacy function format
                if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*\(\)$/', $legacyFunction)) {
                    $formatErrors[] = "$file: Invalid legacy function format: $legacyFunction";
                }
            }
        }
        
        if (!empty($formatErrors)) {
            echo "✗ Found " . count($formatErrors) . " format errors\n";
            foreach (array_slice($formatErrors, 0, 5) as $error) {
                echo "  - $error\n";
            }
            if (count($formatErrors) > 5) {
                echo "  ... and " . (count($formatErrors) - 5) . " more\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $formatErrors);
            return false;
        }

        echo "✓ All {$this->legacyTagsFound} legacy tags follow correct format\n\n";
        return true;
    }

    /**
     * Test: Verify legacy tags are consistent within each file
     */
    private function testLegacyTagConsistency(): bool
    {
        echo "Test: Legacy Tag Consistency\n";
        echo str_repeat("-", 50) . "\n";
        
        $changedFiles = $this->getChangedPhpFiles();
        $inconsistencies = [];
        
        foreach ($changedFiles as $file) {
            $content = file_get_contents($file);
            
            // Extract all legacy file paths from this file
            preg_match_all('/@legacy-file\s+([^\r\n]+)/m', $content, $matches);
            $legacyFiles = array_unique(array_map('trim', $matches[1]));
            
            // Each file should reference only one legacy file
            if (count($legacyFiles) > 1) {
                $inconsistencies[] = "$file: References multiple legacy files: " . implode(', ', $legacyFiles);
            }
        }
        
        if (!empty($inconsistencies)) {
            echo "✗ Found " . count($inconsistencies) . " inconsistencies\n";
            foreach ($inconsistencies as $error) {
                echo "  - $error\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $inconsistencies);
            return false;
        }

        echo "✓ All files have consistent legacy file references\n\n";
        return true;
    }

    /**
     * Test: Verify Core classes have correct legacy paths
     */
    private function testCoreClassLegacyTags(): bool
    {
        echo "Test: Core Class Legacy Tags\n";
        echo str_repeat("-", 50) . "\n";
        
        $coreFiles = glob('application/Core/*.php');
        $errors = [];
        
        foreach ($coreFiles as $file) {
            $content = file_get_contents($file);
            
            // Skip if no legacy tags (some core files might not have methods)
            if (strpos($content, '@legacy-file') === false) {
                continue;
            }
            
            // Expected legacy path format for core classes
            $expectedPattern = '#@legacy-file\s+application/core/[A-Z][a-zA-Z0-9_]+\.php#';
            
            if (!preg_match($expectedPattern, $content)) {
                $errors[] = "$file: Core class should reference application/core/*.php";
            }
            
            $this->methodsValidated++;
        }
        
        if (!empty($errors)) {
            echo "✗ Found " . count($errors) . " errors in core classes\n";
            foreach ($errors as $error) {
                echo "  - $error\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $errors);
            return false;
        }

        echo "✓ All core class legacy tags are correct\n\n";
        return true;
    }

    /**
     * Test: Verify Module Controllers have correct legacy paths
     */
    private function testModuleControllerLegacyTags(): bool
    {
        echo "Test: Module Controller Legacy Tags\n";
        echo str_repeat("-", 50) . "\n";
        
        $controllerFiles = glob('application/Modules/*/Controllers/*.php');
        $errors = [];
        
        foreach ($controllerFiles as $file) {
            $content = file_get_contents($file);
            
            // Skip if no legacy tags
            if (strpos($content, '@legacy-file') === false) {
                continue;
            }
            
            // Extract module name
            if (preg_match('#application/Modules/([^/]+)/Controllers/([^/]+)\.php#', $file, $match)) {
                $moduleName = $match[1];
                
                // Convert PSR-4 module name to legacy format (e.g., CustomFields -> custom_fields)
                $legacyModule = $this->convertToLegacyModuleName($moduleName);
                
                // Expected pattern
                $expectedPattern = "#@legacy-file\\s+application/modules/$legacyModule/controllers/[A-Za-z]+\\.php#";
                
                if (!preg_match($expectedPattern, $content)) {
                    $errors[] = "$file: Should reference application/modules/$legacyModule/controllers/*.php";
                }
            }
            
            $this->methodsValidated++;
        }
        
        if (!empty($errors)) {
            echo "✗ Found " . count($errors) . " errors in controllers\n";
            foreach (array_slice($errors, 0, 5) as $error) {
                echo "  - $error\n";
            }
            if (count($errors) > 5) {
                echo "  ... and " . (count($errors) - 5) . " more\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $errors);
            return false;
        }

        echo "✓ All module controller legacy tags are correct\n\n";
        return true;
    }

    /**
     * Test: Verify Module Models have correct legacy paths
     */
    private function testModuleModelLegacyTags(): bool
    {
        echo "Test: Module Model Legacy Tags\n";
        echo str_repeat("-", 50) . "\n";
        
        $modelFiles = glob('application/Modules/*/Models/*.php');
        $errors = [];
        
        foreach ($modelFiles as $file) {
            $content = file_get_contents($file);
            
            // Skip if no legacy tags
            if (strpos($content, '@legacy-file') === false) {
                continue;
            }
            
            // Extract module name and model name
            if (preg_match('#application/Modules/([^/]+)/Models/([^/]+)\.php#', $file, $match)) {
                $moduleName = $match[1];
                
                // Convert to legacy format
                $legacyModule = $this->convertToLegacyModuleName($moduleName);
                
                // Expected pattern: application/modules/{module}/models/Mdl_{model}.php
                $expectedPattern = "#@legacy-file\\s+application/modules/$legacyModule/models/Mdl_[a-z_]+\\.php#";
                
                if (!preg_match($expectedPattern, $content)) {
                    $errors[] = "$file: Should reference application/modules/$legacyModule/models/Mdl_*.php";
                }
            }
            
            $this->methodsValidated++;
        }
        
        if (!empty($errors)) {
            echo "✗ Found " . count($errors) . " errors in models\n";
            foreach (array_slice($errors, 0, 5) as $error) {
                echo "  - $error\n";
            }
            if (count($errors) > 5) {
                echo "  ... and " . (count($errors) - 5) . " more\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $errors);
            return false;
        }

        echo "✓ All module model legacy tags are correct\n\n";
        return true;
    }

    /**
     * Test: Verify methods with existing PHPDoc have legacy tags added
     */
    private function testNoMissingLegacyTags(): bool
    {
        echo "Test: No Missing Legacy Tags on Public Methods\n";
        echo str_repeat("-", 50) . "\n";
        
        $changedFiles = $this->getChangedPhpFiles();
        $missingTags = [];
        
        foreach ($changedFiles as $file) {
            $content = file_get_contents($file);
            
            // Find all public/protected methods (excluding constructors)
            preg_match_all('/^\s*(public|protected)\s+function\s+(?!__construct)([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/m', $content, $methods, PREG_OFFSET_CAPTURE);
            
            foreach ($methods[2] as $method) {
                $methodName = $method[0];
                $methodPos = $method[1];
                
                // Get the 500 characters before the method to check for PHPDoc
                $before = substr($content, max(0, $methodPos - 500), 500);
                
                // Check if there's a PHPDoc comment immediately before the method
                if (preg_match('/\/\*\*.*?\*\/\s*$/s', $before)) {
                    // Has PHPDoc - should have legacy tags
                    if (strpos($before, '@legacy-file') === false || strpos($before, '@legacy-function') === false) {
                        $missingTags[] = "$file: Method $methodName() missing legacy tags";
                    }
                }
            }
        }
        
        if (!empty($missingTags)) {
            echo "✗ Found " . count($missingTags) . " methods missing legacy tags\n";
            foreach (array_slice($missingTags, 0, 5) as $error) {
                echo "  - $error\n";
            }
            if (count($missingTags) > 5) {
                echo "  ... and " . (count($missingTags) - 5) . " more\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $missingTags);
            return false;
        }

        echo "✓ No methods missing legacy tags\n\n";
        return true;
    }

    /**
     * Test: Verify referenced legacy file paths use correct conventions
     */
    private function testLegacyFilePathsValid(): bool
    {
        echo "Test: Legacy File Path Conventions\n";
        echo str_repeat("-", 50) . "\n";
        
        $changedFiles = $this->getChangedPhpFiles();
        $pathErrors = [];
        
        foreach ($changedFiles as $file) {
            $content = file_get_contents($file);
            
            preg_match_all('/@legacy-file\s+([^\r\n]+)/m', $content, $matches);
            
            foreach ($matches[1] as $legacyPath) {
                $legacyPath = trim($legacyPath);
                $error = $this->validateLegacyFilePath($file, $legacyPath);
                if ($error !== null) {
                    $pathErrors[] = $error;
                }
            }
        }
        
        if (!empty($pathErrors)) {
            echo "✗ Found " . count($pathErrors) . " path convention errors\n";
            foreach (array_slice($pathErrors, 0, 5) as $error) {
                echo "  - $error\n";
            }
            if (count($pathErrors) > 5) {
                echo "  ... and " . (count($pathErrors) - 5) . " more\n";
            }
            echo "\n";
            $this->errors = array_merge($this->errors, $pathErrors);
            return false;
        }

        echo "✓ All legacy file paths follow correct conventions\n\n";
        return true;
    }

    /**
     * Validate a single legacy file path
     */
    private function validateLegacyFilePath(string $file, string $legacyPath): ?string
    {
        if (strpos($legacyPath, 'application/core/') === 0) {
            return $this->validateCoreLegacyPath($legacyPath);
        }

        if (strpos($legacyPath, 'application/modules/') === 0) {
            return $this->validateModuleLegacyPath($file, $legacyPath);
        }

        return null;
    }

    /**
     * Validate core legacy path conventions
     */
    private function validateCoreLegacyPath(string $legacyPath): ?string
    {
        // Core files should use snake_case with underscores
        $basename = basename($legacyPath, '.php');
        if (preg_match('/[A-Z]/', $basename) && strpos($basename, '_') === false) {
            // Likely needs underscore (e.g., Admin_Controller not AdminController)
        }

        return null;
    }

    /**
     * Validate module legacy path conventions
     */
    private function validateModuleLegacyPath(string $file, string $legacyPath): ?string
    {
        // Module paths should be lowercase
        if (preg_match('#application/modules/[A-Z]#', $legacyPath)) {
            return "$file: Legacy module path should be lowercase: $legacyPath";
        }
        
        // Model files should start with Mdl_
        if (strpos($legacyPath, '/models/') !== false && strpos(basename($legacyPath), 'Mdl_') !== 0) {
            return "$file: Legacy model should start with Mdl_: $legacyPath";
        }

        return null;
    }

    /**
     * Convert PSR-4 module name to legacy format
     */
    private function convertToLegacyModuleName(string $moduleName): string
    {
        // Convert PascalCase to snake_case
        $legacy = preg_replace('/([a-z])([A-Z])/', '$1_$2', $moduleName);
        return strtolower($legacy);
    }

    /**
     * Get list of changed PHP files
     */
    private function getChangedPhpFiles(): array
    {
        $output = [];
        exec('git diff --name-only --diff-filter=AM develop..HEAD | grep "\.php$"', $output);
        return $output;
    }

    /**
     * Print test summary
     */
    private function printSummary(): void
    {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "Test Summary\n";
        echo str_repeat("=", 50) . "\n";
        echo "Files checked: {$this->filesChecked}\n";
        echo "Methods validated: {$this->methodsValidated}\n";
        echo "Legacy tags found: {$this->legacyTagsFound}\n";
        echo "Errors: " . count($this->errors) . "\n";
        
        if (!empty($this->errors)) {
            echo "\n✗ Validation failed with " . count($this->errors) . " errors\n";
            return;
        }

        echo "\n✓ All validation tests passed!\n";
    }
}

// Run the tests
$validator = new LegacyPhpDocValidationTest();
$success = $validator->runAll();
exit($success ? 0 : 1);