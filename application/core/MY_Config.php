<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Custom Config Class
 * 
 * Extends CodeIgniter's Config class to use custom config path
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
    public function load($file = '', $use_sections = FALSE, $fail_gracefully = FALSE)
    {
        $file = ($file === '') ? 'config' : str_replace('.php', '', $file);
        $loaded = FALSE;

        // Use custom CONFIGPATH if defined, otherwise fall back to APPPATH
        $config_path = defined('CONFIGPATH') ? CONFIGPATH : APPPATH . 'config/';
        
        foreach ($this->_config_paths as $path) {
            $file_path = $path . 'config/' . $file . '.php';
            
            // Try custom config path first if defined
            if (defined('CONFIGPATH')) {
                $custom_file_path = CONFIGPATH . $file . '.php';
                if (file_exists($custom_file_path)) {
                    $file_path = $custom_file_path;
                }
            }
            
            if (in_array($file_path, $this->is_loaded, TRUE)) {
                return TRUE;
            }

            if (!file_exists($file_path)) {
                continue;
            }

            include($file_path);

            if (!isset($config) || !is_array($config)) {
                if ($fail_gracefully === TRUE) {
                    return FALSE;
                }

                show_error('Your ' . $file_path . ' file does not appear to contain a valid configuration array.');
            }

            if ($use_sections === TRUE) {
                $this->config[$file] = isset($this->config[$file])
                    ? array_merge($this->config[$file], $config)
                    : $config;
            } else {
                $this->config = array_merge($this->config, $config);
            }

            $this->is_loaded[] = $file_path;
            $config = NULL;
            $loaded = TRUE;
            log_message('debug', 'Config file loaded: ' . $file_path);
        }

        if ($loaded === TRUE) {
            return TRUE;
        } elseif ($fail_gracefully === TRUE) {
            return FALSE;
        }

        show_error('The configuration file ' . $file . '.php does not exist.');
    }
}
