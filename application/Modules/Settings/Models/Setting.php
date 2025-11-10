<?php

namespace App\Modules\Settings\Models;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author      InvoicePlane Developers & Contributors
 * @copyright   Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license     https://invoiceplane.com/license.txt
 * @link        https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Setting extends CI_Model
{
    public $settings = [];

    /**
     * @param $key
     * @param $value
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function save()
     */
    public function save($key, $value)
    {
        $db_array = [
            'setting_key'   => $key,
            'setting_value' => $value,
        ];

        if ($this->get($key) !== null) {
            $this->db->where('setting_key', $key);
            $this->db->update('ip_settings', $db_array);
        } else {
            $this->db->insert('ip_settings', $db_array);
        }
    }

    /**
     * @param $key
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function get()
     */
    public function get($key)
    {
        $this->db->select('setting_value');
        $this->db->where('setting_key', $key);

        $query = $this->db->get('ip_settings');

        if ($query->row()) {
            return $query->row()->setting_value;
        }
    }

    /**
     * @param $key
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function delete()
     */
    public function delete($key)
    {
        $this->db->where('setting_key', $key);
        $this->db->delete('ip_settings');
    }

    /**
     * Loads all settings from the database so they are available
     * without additional queries.
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function load_settings()
     */
    public function load_settings()
    {
        // Load all settings from the database
        $ip_settings = $this->db->get('ip_settings')->result();

        foreach ($ip_settings as $data) {
            $this->setting[$data->setting_key] = $data->setting_value;
        }

        // Append current version to the settings
        $this->load->model('settings/mdl_version');
        $this->setting['current_version'] = $this->mdl_versions->get_current_version();
    }

    /**
     * @param        $key
     * @param string $default
     *
     * @return mixed|string
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function setting()
     */
    public function setting($key, $default = '')
    {
        return (isset($this->setting[$key]) && $this->setting[$key] !== '') ? $this->setting[$key] : $default;
    }

    /**
     * @param string $key
     *
     * @return mixed|string
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function gateway_settings()
     */
    public function gateway_settings($key)
    {
        return $this->db->like('setting_key', 'gateway_' . mb_strtolower($key), 'after')->get('ip_settings')->result();
    }

    /**
     * @param $key
     * @param $value
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function set_setting()
     */
    public function set_setting($key, $value)
    {
        $this->setting[$key] = $value;
    }

    /**
     * Returns all available themes.
     *
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_setting.php
     * @legacy-function get_themes()
     */
    public function get_themes()
    {
        $this->load->helper('directory');

        $found_folders = directory_map(THEME_FOLDER, 1);

        $themes = [];

        foreach ($found_folders as $theme) {
            if ($theme == 'core') {
                continue;
            }

            // Get the theme info file
            $theme     = str_replace(DIRECTORY_SEPARATOR, '', $theme);
            $info_path = THEME_FOLDER . $theme . '/';
            $info_file = $theme . '.theme';

            if (file_exists($info_path . $info_file)) {
                $theme_info = Dotenv\Dotenv::createMutable($info_path, $info_file);
                $theme_info->load();
                $themes[$theme] = env('TITLE');
            }
        }

        return $themes;
    }
}
