<?php
define('BASEPATH', '/tmp/');
define('EXT', '.php');

// Manually load MX_Controller and CI_Model as dummies for testing
class CI_Model {}
class MX_Controller {}

// Now require the bootstrap file
require_once 'application/core/bootstrap_core.php';

echo "Testing core class loading...\n\n";

$classes = [
    'Admin_Controller',
    'Base_Controller',
    'User_Controller',
    'Guest_Controller',
    'MY_Model',
    'Response_Model',
    'Form_Validation_Model',
    'Validator',
];

foreach ($classes as $class) {
    $psr4Class = "App\\Core\\$class";
    
    $globalExists = class_exists($class, false);
    $psr4Exists = class_exists($psr4Class, false);
    
    if ($globalExists && $psr4Exists) {
        echo "✓ $class (both global and PSR-4)\n";
    } else if ($globalExists) {
        echo "⚠ $class (global only)\n";
    } else if ($psr4Exists) {
        echo "⚠ $class (PSR-4 only)\n";
    } else {
        echo "✗ $class (NOT FOUND)\n";
    }
}

echo "\nDone!\n";
