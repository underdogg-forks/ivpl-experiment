<?php

namespace App\Modules\Invoices\Models;

if (! defined('BASEPATH')) {
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

#[\AllowDynamicProperties]
class Invoice extends \Response_Model
{
    public $table = 'ip_invoices';

    public $primary_key = 'ip_invoices.invoice_id';

    public $date_modified_field = 'invoice_date_modified';

    /**
     * @return array
     */
    public function statuses(): array
    {
        return [
            '1' => [
                'label' => trans('draft'),
                'class' => 'draft',
                'href'  => 'invoices/status/draft',
            ],
            '2' => [
                'label' => trans('sent'),
                'class' => 'sent',
                'href'  => 'invoices/status/sent',
            ],
            '3' => [
                'label' => trans('viewed'),
                'class' => 'viewed',
                'href'  => 'invoices/status/viewed',
            ],
            '4' => [
                'label' => trans('paid'),
                'class' => 'paid',
                'href'  => 'invoices/status/paid',
            ],
        ];
    }

    public function default_select(): void
    {
        $this->db->select("
            SQL_CALC_FOUND_ROWS
            ip_quotes.*,
            ip_users.*,
            ip_clients.*,
            ip_invoice_sumex.*,
            ip_invoice_amounts.invoice_amount_id,
            IFnull(ip_invoice_amounts.invoice_item_subtotal, '0.00') AS invoice_item_subtotal,
            IFnull(ip_invoice_amounts.invoice_item_tax_total, '0.00') AS invoice_item_tax_total,
            IFnull(ip_invoice_amounts.invoice_tax_total, '0.00') AS invoice_tax_total,
            IFnull(ip_invoice_amounts.invoice_total, '0.00') AS invoice_total,
            IFnull(ip_invoice_amounts.invoice_paid, '0.00') AS invoice_paid,
            IFnull(ip_invoice_amounts.invoice_balance, '0.00') AS invoice_balance,
            ip_invoice_amounts.invoice_sign AS invoice_sign,
            (CASE WHEN ip_invoices.invoice_status_id NOT IN (1,4) AND DATEDIFF(NOW(), invoice_date_due) > 0 THEN 1 ELSE 0 END) is_overdue,
            DATEDIFF(NOW(), invoice_date_due) AS days_overdue,
            (CASE (SELECT COUNT(*) FROM ip_invoices_recurring WHERE ip_invoices_recurring.invoice_id = ip_invoices.invoice_id and ip_invoices_recurring.recur_next_date IS NOT NULL) WHEN 0 THEN 0 ELSE 1 END) AS invoice_is_recurring,
            ip_invoices.*", false);
    }

    public function default_order_by(): void
    {
        $this->db->order_by('ip_invoices.invoice_date_created DESC, ip_invoices.invoice_number DESC, ip_invoices.invoice_id DESC');
    }

    public function default_join(): void
    {
        $this->db->join('ip_clients', 'ip_clients.client_id = ip_invoices.client_id', 'left');
        $this->db->join('ip_invoice_amounts', 'ip_invoice_amounts.invoice_id = ip_invoices.invoice_id', 'left');
        $this->db->join('ip_users', 'ip_users.user_id = ip_invoices.user_id', 'left');
        $this->db->join('ip_invoice_sumex', 'ip_invoice_sumex.invoice_id = ip_invoices.invoice_id', 'left');
    }

    public function validation_rules(): array
    {
        return [
            'client_id' => [
                'field' => 'client_id',
                'label' => trans('client'),
                'rules' => 'required',
            ],
            'invoice_date_created' => [
                'field' => 'invoice_date_created',
                'label' => trans('invoice_date'),
                'rules' => 'required',
            ],
            'invoice_group_id' => [
                'field' => 'invoice_group_id',
                'label' => trans('invoice_group'),
                'rules' => 'required',
            ],
        ];
    }

    public function db_array(): array
    {
        $db_array = parent::db_array();

        // Set defaults for new invoices
        if (! isset($db_array['invoice_id'])) {
            $db_array['invoice_status_id'] = 1; // Draft
            $db_array['invoice_date_created'] = date('Y-m-d');
            $db_array['user_id'] = $this->session->userdata('user_id');
        }

        return $db_array;
    }

    public function is_draft(): void
    {
        $this->filter_where('ip_invoices.invoice_status_id', 1);
    }

    public function is_sent(): void
    {
        $this->filter_where('ip_invoices.invoice_status_id', 2);
    }

    public function is_viewed(): void
    {
        $this->filter_where('ip_invoices.invoice_status_id', 3);
    }

    public function is_paid(): void
    {
        $this->filter_where('ip_invoices.invoice_status_id', 4);
    }

    public function is_overdue(): void
    {
        $this->filter_having('is_overdue', 1);
    }

    public function get_archives($year): array
    {
        $archives = [];
        
        // Implementation would go here
        // This is a placeholder
        
        return $archives;
    }
}
