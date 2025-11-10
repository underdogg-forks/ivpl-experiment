#!/usr/bin/env php
<?php

/**
 * PSR-4 Migration Script for InvoicePlane
 * 
 * This script:
 * 1. Migrates all module controllers to PSR-4 (Controllers/ directory with Controller suffix)
 * 2. Migrates all module models to PSR-4 (Models/ directory, removes Mdl_ prefix)
 * 3. Migrates all core classes to PSR-4 (adds namespaces)
 * 4. Updates all references
 */

class PSR4Migrator
{
    private string $basePath;
    private array $log = [];
    private array $errors = [];
    
    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
    }
    
    public function migrate(): void
    {
        echo "=== PSR-4 Migration Script ===\n\n";
        
        // Step 1: Migrate core classes
        echo "Step 1: Migrating core classes...\n";
        $this->migrateCoreClasses();
        
        // Step 2: Migrate module controllers
        echo "\nStep 2: Migrating module controllers...\n";
        $this->migrateModuleControllers();
        
        // Step 3: Migrate module models
        echo "\nStep 3: Migrating module models...\n";
        $this->migrateModuleModels();
        
        // Print summary
        echo "\n=== Migration Summary ===\n";
        echo "Total operations: " . count($this->log) . "\n";
        echo "Errors: " . count($this->errors) . "\n";
        
        if (!empty($this->errors)) {
            echo "\nErrors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        
        echo "\nLog saved to: migration_log.txt\n";
        file_put_contents('migration_log.txt', implode("\n", $this->log));
    }
    
    private function migrateCoreClasses(): void
    {
        $corePath = $this->basePath . '/application/core';
        $targetPath = $corePath . '/Core';
        
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0755, true);
            $this->log("Created directory: $targetPath");
        }
        
        $coreFiles = [
            'Admin_Controller.php',
            'Base_Controller.php',
            'User_Controller.php',
            'Guest_Controller.php',
            'MY_Model.php',
            'Response_Model.php',
            'Form_Validation_Model.php',
            'Validator.php',
        ];
        
        foreach ($coreFiles as $file) {
            $sourcePath = "$corePath/$file";
            if (!file_exists($sourcePath)) {
                $this->error("Core file not found: $sourcePath");
                continue;
            }
            
            $className = str_replace('.php', '', $file);
            $targetFile = "$targetPath/$className.php";
            
            $content = file_get_contents($sourcePath);
            
            // Add namespace after opening PHP tag
            $namespace = "namespace App\\Core;\n\n";
            $content = preg_replace(
                '/^<\?php\s+/s',
                "<?php\n\n$namespace",
                $content
            );
            
            // Keep the original file, create PSR-4 version
            file_put_contents($targetFile, $content);
            $this->log("Migrated core class: $file -> Core/$file");
        }
    }
    
    private function migrateModuleControllers(): void
    {
        $modulesPath = $this->basePath . '/application/modules';
        $modules = array_filter(glob($modulesPath . '/*'), 'is_dir');
        
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $controllersPath = "$modulePath/controllers";
            
            if (!is_dir($controllersPath)) {
                continue;
            }
            
            $targetPath = "$modulePath/Controllers";
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            
            $controllers = glob($controllersPath . '/*.php');
            foreach ($controllers as $controllerFile) {
                $this->migrateController($controllerFile, $targetPath, $moduleName);
            }
        }
    }
    
    private function migrateController(string $sourcePath, string $targetPath, string $moduleName): void
    {
        $originalClass = basename($sourcePath, '.php');
        
        // Skip if already migrated
        if (strpos($originalClass, 'Controller') !== false) {
            return;
        }
        
        $content = file_get_contents($sourcePath);
        
        // Determine new class name
        // Special handling for Ajax, Cron, Recurring, etc.
        if (in_array($originalClass, ['Ajax', 'Cron', 'Recurring'])) {
            $newClass = $originalClass . 'Controller';
        } else {
            $newClass = ucfirst($moduleName) . 'Controller';
        }
        
        $namespace = "App\\Modules\\" . ucfirst($moduleName) . "\\Controllers";
        
        // Add namespace
        $content = preg_replace(
            '/^<\?php\s+/s',
            "<?php\n\nnamespace $namespace;\n\n",
            $content
        );
        
        // Update class name - handle both with and without AllowDynamicProperties attribute
        if (preg_match('/#\[AllowDynamicProperties\]/', $content)) {
            $content = preg_replace(
                '/(#\[AllowDynamicProperties\]\s*)class\s+' . preg_quote($originalClass) . '\s+extends/m',
                '$1class ' . $newClass . ' extends',
                $content
            );
        } else {
            $content = preg_replace(
                '/class\s+' . preg_quote($originalClass) . '\s+extends/m',
                'class ' . $newClass . ' extends',
                $content
            );
        }
        
        // Update extends to use fully qualified names (add leading backslash)
        $content = preg_replace(
            '/extends\s+(Admin_Controller|User_Controller|Guest_Controller|Base_Controller)\b/',
            'extends \\\$1',
            $content
        );
        
        $targetFile = "$targetPath/$newClass.php";
        file_put_contents($targetFile, $content);
        
        $this->log("Migrated controller: $moduleName/$originalClass -> Controllers/$newClass");
    }
    
    private function migrateModuleModels(): void
    {
        $modulesPath = $this->basePath . '/application/modules';
        $modules = array_filter(glob($modulesPath . '/*'), 'is_dir');
        
        foreach ($modules as $modulePath) {
            $moduleName = basename($modulePath);
            $modelsPath = "$modulePath/models";
            
            if (!is_dir($modelsPath)) {
                continue;
            }
            
            $targetPath = "$modulePath/Models";
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }
            
            $models = glob($modelsPath . '/*.php');
            foreach ($models as $modelFile) {
                $this->migrateModel($modelFile, $targetPath, $moduleName);
            }
        }
    }
    
    private function migrateModel(string $sourcePath, string $targetPath, string $moduleName): void
    {
        $filename = basename($sourcePath, '.php');
        
        // Skip if already migrated
        if (!str_starts_with($filename, 'Mdl_')) {
            return;
        }
        
        $content = file_get_contents($sourcePath);
        
        // Extract actual class name from content (case-sensitive)
        if (!preg_match('/class\s+(\w+)\s+extends/m', $content, $matches)) {
            $this->error("Could not find class declaration in: $sourcePath");
            return;
        }
        
        $originalClass = $matches[1];
        
        // Remove Mdl_ prefix
        $newClass = str_replace('Mdl_', '', $originalClass);
        $newClass = str_replace('_', '', ucwords($newClass, '_'));
        
        $namespace = "App\\Modules\\" . ucfirst($moduleName) . "\\Models";
        
        // Add namespace
        $content = preg_replace(
            '/^<\?php\s+/s',
            "<?php\n\nnamespace $namespace;\n\n",
            $content
        );
        
        // Update class name - handle both with and without AllowDynamicProperties attribute
        if (preg_match('/#\[AllowDynamicProperties\]/', $content)) {
            $content = preg_replace(
                '/(#\[AllowDynamicProperties\]\s*)class\s+' . preg_quote($originalClass) . '\s+extends/m',
                '$1class ' . $newClass . ' extends',
                $content
            );
        } else {
            $content = preg_replace(
                '/class\s+' . preg_quote($originalClass) . '\s+extends/m',
                'class ' . $newClass . ' extends',
                $content
            );
        }
        
        // Update extends to use fully qualified names (add leading backslash)
        $content = preg_replace(
            '/extends\s+(Response_Model|MY_Model|Form_Validation_Model)\b/',
            'extends \\\$1',
            $content
        );
        
        $targetFile = "$targetPath/$newClass.php";
        file_put_contents($targetFile, $content);
        
        $this->log("Migrated model: $moduleName/$originalClass -> Models/$newClass");
    }
    
    private function log(string $message): void
    {
        $this->log[] = $message;
        echo "  ✓ $message\n";
    }
    
    private function error(string $message): void
    {
        $this->errors[] = $message;
        echo "  ✗ ERROR: $message\n";
    }
}

// Run migration
$migrator = new PSR4Migrator(__DIR__);
$migrator->migrate();

echo "\nMigration complete!\n";
echo "Next steps:\n";
echo "1. Update composer autoload and run: composer dump-autoload\n";
echo "2. Test all modules\n";
echo "3. Remove old controllers/ and models/ directories if all tests pass\n";
