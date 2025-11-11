<?php

defined('BASEPATH') || exit('No direct script access allowed');

/*
| -------------------------------------------------------------------------
| Hooks
| -------------------------------------------------------------------------
| This file lets you define "hooks" to extend CI without hacking the core
| files.  Please see the user guide for info:
|
|   https://codeigniter.com/user_guide/general/hooks.html
|
*/

// Initialize exception handler first
$hook['pre_system'] = [
    'class'    => 'ExceptionHandler',
    'function' => 'init',
    'filename' => 'ExceptionHandler.php',
    'filepath' => 'hooks',
];

$hook['pre_controller'] = [
    'class'    => 'SetTimezoneClass',
    'function' => 'setTimezone',
    'filename' => 'SetTimezoneClass.php',
    'filepath' => 'hooks',
];
