<?php

namespace App\Modules\CustomFields\Models;

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
class InvoiceCustom extends Validator
{
    public static $positions = [
        'custom_fields',
        'properties',
    ];

    public $table = 'ip_invoice_custom';

    public $primary_key = 'ip_invoice_custom.invoice_custom_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/models/Mdl_invoice_custom.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS ip_invoice_custom.*, ip_custom_fields.*', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/models/Mdl_invoice_custom.php
     * @legacy-function default_join()
     */
    public function default_join()
    {
        $this->db->join('ip_custom_fields', 'ip_invoice_custom.invoice_custom_fieldid = ip_custom_fields.custom_field_id');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/models/Mdl_invoice_custom.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('custom_field_table ASC, custom_field_order ASC, custom_field_label ASC');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/models/Mdl_invoice_custom.php
     * @legacy-function save_custom()
     */
    public function save_custom($invoice_id, $db_array)
    {
        $result = $this->validate($db_array);
        if ($result === true) {
            $form_data = property_exists($this, '_formdata') && $this->_formdata !== null ? $this->_formdata : null;

            if (null === $form_data) {
                return true;
            }

            $invoice_custom_id = null;

            foreach ($form_data as $key => $value) {
                $db_array = [
                    'invoice_id'                => $invoice_id,
                    'invoice_custom_fieldid'    => $key,
                    'invoice_custom_fieldvalue' => $value,
                ];
                $invoice_custom = $this->where('invoice_id', $invoice_id)->where('invoice_custom_fieldid', $key)->get();

                if ($invoice_custom->num_rows()) {
                    $invoice_custom_id = $invoice_custom->row()->invoice_custom_id;
                }

                // why not delete when it empty value (clean db)

                parent::save($invoice_custom_id, $db_array);
            }

            return true;
        }

        return $result;
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/models/Mdl_invoice_custom.php
     * @legacy-function by_id()
     */
    public function by_id($invoice_id)
    {
        $this->db->where('ip_invoice_custom.invoice_id', $invoice_id);

        return $this;
    }
}
