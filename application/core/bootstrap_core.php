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
    'MyModel',
    'FormValidationModel',
    'ResponseModel',
    'Validator',
    'BaseController',
    'UserController',
    'AdminController',
    'GuestController',
];

foreach ($coreClasses as $class) {
    $psr4Class = "App\\Core\\$class";
    $filePath = __DIR__ . "/../Core/$class.php";
    
    // Load the PSR-4 class file
    if (file_exists($filePath)) {
        require_once $filePath;
        
        // Create global alias for backward compatibility (convert to old names with underscores)
        $legacyClass = $class;
        // Convert PascalCase to Snake_Case for legacy compatibility
        if ($class === 'MyModel') {
            $legacyClass = 'MY_Model';
        } elseif ($class === 'FormValidationModel') {
            $legacyClass = 'Form_Validation_Model';
        } elseif ($class === 'ResponseModel') {
            $legacyClass = 'Response_Model';
        } elseif ($class === 'BaseController') {
            $legacyClass = 'Base_Controller';
        } elseif ($class === 'UserController') {
            $legacyClass = 'User_Controller';
        } elseif ($class === 'AdminController') {
            $legacyClass = 'Admin_Controller';
        } elseif ($class === 'GuestController') {
            $legacyClass = 'Guest_Controller';
        }
        
        if (class_exists($psr4Class) && !class_exists($legacyClass, false)) {
            class_alias($psr4Class, $legacyClass);
        }
    }
}
