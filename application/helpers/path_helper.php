<?php

/**
 * Path Helpers
 * 
 * Modern helper functions for path manipulation.
 * These helpers work with or without CodeIgniter constants being defined.
 */

if (!function_exists('app_path')) {
    /**
     * Get the path to the application directory
     * 
     * @param string $path Optional path to append
     * @return string The full application path
     */
    function app_path(string $path = ''): string
    {
        $base = defined('APPPATH') ? APPPATH : '';
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the path to the base directory (project root)
     * 
     * @param string $path Optional path to append
     * @return string The full base path
     */
    function base_path(string $path = ''): string
    {
        $base = defined('FCPATH') ? dirname(FCPATH) . DIRECTORY_SEPARATOR : '';
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public directory
     * 
     * @param string $path Optional path to append
     * @return string The full public path
     */
    function public_path(string $path = ''): string
    {
        $base = defined('FCPATH') ? FCPATH : '';
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory
     * 
     * @param string $path Optional path to append
     * @return string The full storage path
     */
    function storage_path(string $path = ''): string
    {
        $base = base_path('storage') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the path to the config directory
     * 
     * @param string $path Optional path to append
     * @return string The full config path
     */
    function config_path(string $path = ''): string
    {
        $base = defined('CONFIGPATH') ? CONFIGPATH : base_path('config') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('uploads_path')) {
    /**
     * Get the path to the uploads directory
     * 
     * @param string $path Optional path to append
     * @return string The full uploads path
     */
    function uploads_path(string $path = ''): string
    {
        // Modern storage location: storage/uploads
        $base = storage_path('uploads') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('logs_path')) {
    /**
     * Get the path to the logs directory
     * 
     * @param string $path Optional path to append
     * @return string The full logs path
     */
    function logs_path(string $path = ''): string
    {
        // Logs remain in application/logs for CodeIgniter compatibility
        $base = app_path('logs') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('view_path')) {
    /**
     * Get the path to the views directory
     * 
     * @param string $path Optional path to append
     * @return string The full views path
     */
    function view_path(string $path = ''): string
    {
        $base = defined('VIEWPATH') ? VIEWPATH : app_path('views') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('asset_path')) {
    /**
     * Get the path to the assets directory
     * 
     * @param string $path Optional path to append
     * @return string The full assets path
     */
    function asset_path(string $path = ''): string
    {
        $base = defined('THEME_FOLDER') ? THEME_FOLDER : public_path('assets') . DIRECTORY_SEPARATOR;
        return $path ? $base . ltrim($path, DIRECTORY_SEPARATOR) : rtrim($base, DIRECTORY_SEPARATOR);
    }
}

if (!function_exists('uploads_temp_path')) {
    /**
     * Get the path to the temporary uploads directory
     * 
     * @param string $path Optional path to append
     * @return string The full temporary uploads path
     */
    function uploads_temp_path(string $path = ''): string
    {
        return uploads_path('temp' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('uploads_customer_files_path')) {
    /**
     * Get the path to the customer files uploads directory
     * 
     * @param string $path Optional path to append
     * @return string The full customer files path
     */
    function uploads_customer_files_path(string $path = ''): string
    {
        return uploads_path('customer_files' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('uploads_archive_path')) {
    /**
     * Get the path to the archive uploads directory
     * 
     * @param string $path Optional path to append
     * @return string The full archive path
     */
    function uploads_archive_path(string $path = ''): string
    {
        return uploads_path('archive' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : ''));
    }
}

if (!function_exists('normalize_path')) {
    /**
     * Normalize a filesystem path
     * 
     * @param string $path The path to normalize
     * @return string The normalized path
     */
    function normalize_path(string $path): string
    {
        // Replace forward slashes and backslashes with the system directory separator
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        
        // Remove duplicate separators
        $path = preg_replace('#' . preg_quote(DIRECTORY_SEPARATOR, '#') . '+#', DIRECTORY_SEPARATOR, $path);
        
        return $path;
    }
}

if (!function_exists('join_paths')) {
    /**
     * Join multiple path segments
     * 
     * @param string ...$paths Path segments to join
     * @return string The joined path
     */
    function join_paths(string ...$paths): string
    {
        if (empty($paths)) {
            return '';
        }
        
        $normalized = array_map(function($path) {
            return trim($path, '/\\');
        }, $paths);
        
        return normalize_path(implode(DIRECTORY_SEPARATOR, $normalized));
    }
}
