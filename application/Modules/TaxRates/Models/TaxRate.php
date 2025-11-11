<?php

namespace App\Modules\TaxRates\Models;

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
class TaxRate extends \Response_Model
{
    public $table = 'ip_tax_rates';

    public $primary_key = 'ip_tax_rates.tax_rate_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/tax_rates/models/Mdl_tax_rate.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/tax_rates/models/Mdl_tax_rate.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_tax_rates.tax_rate_percent');
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/tax_rates/models/Mdl_tax_rate.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'tax_rate_name' => [
                'field' => 'tax_rate_name',
                'label' => trans('tax_rate_name'),
                'rules' => 'required',
            ],
            'tax_rate_percent' => [
                'field' => 'tax_rate_percent',
                'label' => trans('tax_rate_percent'),
                'rules' => 'required',
            ],
        ];
    }
}
