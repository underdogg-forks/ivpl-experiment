<?php

namespace App\Modules\Units\Models;

use App\Core\ResponseModel;

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
class Unit extends ResponseModel
{
    public $table = 'ip_units';

    public $primary_key = 'ip_units.unit_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/units/models/Mdl_unit.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/units/models/Mdl_unit.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_units.unit_name');
    }

    /**
     * Return either the singular unit name or the plural unit name,
     * depending on the quantity.
     *
     * @param $unit_id
     * @param $quantity
     *
     * @return mixed
     *
     * Legacy migration info:
     * @legacy-file application/modules/units/models/Mdl_unit.php
     * @legacy-function get_name()
     */
    public function get_name($unit_id, $quantity)
    {
        if ($unit_id) {
            $units = $this->get()->result();
            foreach ($units as $unit) {
                if ($unit->unit_id == $unit_id) {
                    if ($quantity < -1 || $quantity > 1) { // Fix 0
                        return $unit->unit_name_plrl;
                    }

                    return $unit->unit_name;
                }
            }
        }
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/units/models/Mdl_unit.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'unit_name' => [
                'field' => 'unit_name',
                'label' => trans('unit_name'),
                'rules' => 'required',
            ],
            'unit_name_plrl' => [
                'field' => 'unit_name_plrl',
                'label' => trans('unit_name_plrl'),
                'rules' => 'required',
            ],
        ];
    }
}
