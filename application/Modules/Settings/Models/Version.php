<?php

namespace App\Modules\Settings\Models;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

#[AllowDynamicProperties]
class Version extends \Response_Model
{
    public $table = 'ip_versions';

    public $primary_key = 'ip_versions.version_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_version.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_version.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_versions.version_date_applied DESC, ip_versions.version_file DESC');
    }

    /**
     * Returns the latest version from the database.
     *
     * @return string
     *
     * Legacy migration info:
     * @legacy-file application/modules/settings/models/Mdl_version.php
     * @legacy-function get_current_version()
     */
    public function get_current_version()
    {
        $current_version = $this->mdl_versions->limit(1)->get()->row()->version_file;

        return str_replace('.sql', '', mb_substr($current_version, mb_strpos($current_version, '_') + 1));
    }
}
