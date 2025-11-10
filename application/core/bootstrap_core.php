<?php

if (! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * PSR-4 Core Classes Bootstrap
 * 
 * This file loads all PSR-4 core classes and creates global aliases
 * for backward compatibility with CodeIgniter and MX.
 * 
 * Load order is important due to class inheritance:
 * 1. MY_Model (extends CI_Model)
 * 2. Form_Validation_Model (extends MY_Model)
 * 3. Response_Model (extends Form_Validation_Model)
 * 4. Validator (extends MY_Model)
 * 5. Base_Controller (extends MX_Controller)
 * 6. User_Controller (extends Base_Controller)
 * 7. Admin_Controller (extends User_Controller)
 * 8. Guest_Controller (extends User_Controller)
 */

// Load all PSR-4 core classes in dependency order
$coreClasses = [
    'MY_Model',
    'Form_Validation_Model',
    'Response_Model',
    'Validator',
    'Base_Controller',
    'User_Controller',
    'Admin_Controller',
    'Guest_Controller',
];

foreach ($coreClasses as $class) {
    $psr4Class = "App\\Core\\$class";
    $filePath = __DIR__ . "/Core/$class.php";
    
    // Load the PSR-4 class file
    if (file_exists($filePath)) {
        require_once $filePath;
        
        // Create global alias for backward compatibility
        if (class_exists($psr4Class) && !class_exists($class, false)) {
            class_alias($psr4Class, $class);
        }
    }
}
