<?php
defined('BASEPATH') || exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Filesystem Configuration
|--------------------------------------------------------------------------
|
| This file contains paths for file storage locations.
| 
| Modern applications should use path helpers instead of defines:
| - uploads_path() - Main uploads directory (storage/uploads)
| - uploads_temp_path() - Temporary files
| - uploads_archive_path() - Archived PDFs
| - uploads_customer_files_path() - Customer file uploads
| - logs_path() - Application logs
|
*/

$config['filesystem'] = [
    
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk.
    |
    */
    'default' => 'local',
    
    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Configure filesystem disks for different storage locations.
    |
    */
    'disks' => [
        
        'local' => [
            'driver' => 'local',
            'root' => storage_path(),
        ],
        
        'uploads' => [
            'driver' => 'local',
            'root' => uploads_path(),
            'visibility' => 'private',
        ],
        
        'temp' => [
            'driver' => 'local',
            'root' => uploads_temp_path(),
            'visibility' => 'private',
        ],
        
        'archive' => [
            'driver' => 'local',
            'root' => uploads_archive_path(),
            'visibility' => 'private',
        ],
        
        'customer_files' => [
            'driver' => 'local',
            'root' => uploads_customer_files_path(),
            'visibility' => 'private',
        ],
        
        'logs' => [
            'driver' => 'local',
            'root' => logs_path(),
            'visibility' => 'private',
        ],
        
    ],
    
];
