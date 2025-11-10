<?php

/**
 * Create alias files for backward compatibility
 */

$coreClasses = [
    'Admin_Controller',
    'Base_Controller',
    'User_Controller',
    'Guest_Controller',
    'MY_Model',
    'Response_Model',
    'Form_Validation_Model',
    'Validator',
];

$basePath = __DIR__ . '/application/core';

foreach ($coreClasses as $class) {
    $content = <<<PHP
<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * PSR-4 Compatibility Alias
 * This file creates a global alias for the PSR-4 namespaced class
 */

// Load the PSR-4 class if not already loaded
if (!class_exists('App\\Core\\$class')) {
    require_once __DIR__ . '/Core/$class.php';
}

// Create global alias for backward compatibility
if (!class_exists('$class', false)) {
    class_alias('App\\Core\\$class', '$class');
}

PHP;
    
    file_put_contents("$basePath/$class.php", $content);
    echo "Created alias file: $class.php\n";
}

echo "\nDone!\n";
