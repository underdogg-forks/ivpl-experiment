<?php

namespace App\Modules\Payments\Models;

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
class PaymentLogs extends \Response_Model
{
    public $table = 'ip_merchant_responses';

    public $primary_key = 'ip_merchant_responses.merchant_response_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payments/models/Mdl_payment_logs.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('
            SQL_CALC_FOUND_ROWS
            ip_invoices.invoice_number,
            ip_merchant_responses.*', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payments/models/Mdl_payment_logs.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_merchant_responses.merchant_response_id DESC');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payments/models/Mdl_payment_logs.php
     * @legacy-function default_join()
     */
    public function default_join()
    {
        $this->db->join('ip_invoices', 'ip_invoices.invoice_id = ip_merchant_responses.invoice_id');
    }
}
