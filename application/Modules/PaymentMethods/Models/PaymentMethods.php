<?php

namespace App\Modules\PaymentMethods\Models;

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
class PaymentMethods extends \Response_Model
{
    public $table = 'ip_payment_methods';

    public $primary_key = 'ip_payment_methods.payment_method_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/models/Mdl_payment_methods.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/models/Mdl_payment_methods.php
     * @legacy-function order_by()
     */
    public function order_by()
    {
        $this->db->order_by('ip_payment_methods.payment_method_name');
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/models/Mdl_payment_methods.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'payment_method_name' => [
                'field' => 'payment_method_name',
                'label' => trans('payment_method'),
                'rules' => 'required',
            ],
        ];
    }
}
