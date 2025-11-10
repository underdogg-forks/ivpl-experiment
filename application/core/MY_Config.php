<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Custom Config Class
 * 
 * Extends CodeIgniter's Config class to use Laravel-style config path
 * 
 * Single Responsibility: Handles config file loading with custom path support
 * Open/Closed: Extends CI_Config without modifying it
 * DRY: Extracts repeated logic into private methods
 */
class MY_Config extends CI_Config
{
    /**
     * Load Config File
     *
     * @param string $file            Configuration file name
     * @param bool   $use_sections    Whether configuration values should be loaded into their own section
     * @param bool   $fail_gracefully Whether to just return FALSE or display an error message
     * @return bool TRUE if the file was loaded correctly
     */
    public function load($file = '', $use_sections = false, $fail_gracefully = false)
    {
        $file = $this->normalizeFileName($file);
        
        foreach ($this->_config_paths as $path) {
            $file_path = $this->resolveFilePath($path, $file);
            
            // Early return: already loaded
            if ($this->isAlreadyLoaded($file_path)) {
                return true;
            }

            // Early return: file doesn't exist
            if (!file_exists($file_path)) {
                continue;
            }

            // Load and validate config file
            if (!$this->loadConfigFile($file_path, $file, $use_sections, $fail_gracefully)) {
                return false;
            }

            return true;
        }

        // Early return: fail gracefully
        if ($fail_gracefully) {
            return false;
        }

        show_error('The configuration file ' . $file . '.php does not exist.');
    }

    /**
     * Normalize config file name
     * DRY: Extracts file name normalization logic
     *
     * @param string $file
     * @return string
     */
    private function normalizeFileName($file)
    {
        return ($file === '') ? 'config' : str_replace('.php', '', $file);
    }

    /**
     * Resolve file path with custom config directory support
     * DRY: Extracts path resolution logic
     *
     * @param string $base_path
     * @param string $file
     * @return string
     */
    private function resolveFilePath($base_path, $file)
    {
        // Try custom CONFIGPATH first (Laravel-style config directory)
        if (defined('CONFIGPATH')) {
            $custom_file_path = CONFIGPATH . $file . '.php';
            if (file_exists($custom_file_path)) {
                return $custom_file_path;
            }
        }

        return $base_path . 'config/' . $file . '.php';
    }

    /**
     * Check if config file is already loaded
     * DRY: Extracts duplicate check logic
     *
     * @param string $file_path
     * @return bool
     */
    private function isAlreadyLoaded($file_path)
    {
        return in_array($file_path, $this->is_loaded, true);
    }

    /**
     * Load and process config file
     * Single Responsibility: Handles file loading and config merging
     *
     * @param string $file_path
     * @param string $file
     * @param bool $use_sections
     * @param bool $fail_gracefully
     * @return bool
     */
    private function loadConfigFile($file_path, $file, $use_sections, $fail_gracefully)
    {
        include($file_path);

        // Early return: invalid config
        if (!isset($config) || !is_array($config)) {
            if ($fail_gracefully) {
                return false;
            }
            show_error('Your ' . $file_path . ' file does not appear to contain a valid configuration array.');
        }

        $this->mergeConfig($file, $config, $use_sections);
        $this->is_loaded[] = $file_path;
        
        log_message('debug', 'Config file loaded: ' . $file_path);
        
        return true;
    }

    /**
     * Merge config into existing configuration
     * Single Responsibility: Handles config array merging
     *
     * @param string $file
     * @param array $config
     * @param bool $use_sections
     * @return void
     */
    private function mergeConfig($file, array $config, $use_sections)
    {
        if ($use_sections) {
            $this->config[$file] = isset($this->config[$file])
                ? array_merge($this->config[$file], $config)
                : $config;
        } else {
            $this->config = array_merge($this->config, $config);
        }
    }
}
