<?php

namespace App\Modules\Families\Models;

use App\Core\ResponseModel;

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
class Family extends ResponseModel
{
    public $table = 'ip_families';

    public $primary_key = 'ip_families.family_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/families/models/Mdl_family.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/families/models/Mdl_family.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_families.family_name');
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/families/models/Mdl_family.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'family_name' => [
                'field' => 'family_name',
                'label' => trans('family_name'),
                'rules' => 'required',
            ],
        ];
    }
}
