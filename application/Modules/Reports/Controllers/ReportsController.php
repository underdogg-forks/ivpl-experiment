<?php

namespace App\Modules\Reports\Controllers;

use App\Core\AdminController;

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
class ReportsController extends AdminController
{
    /**
     * Reports constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('reports/report');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/reports/controllers/Reports.php
     * @legacy-function sales_by_client()
     */
    public function sales_by_client()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->report->sales_by_client($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/sales_by_client', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('sales_by_client'), true);
        }

        $this->layout->buffer('content', 'reports/sales_by_client_index')->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/reports/controllers/Reports.php
     * @legacy-function invoices_per_client()
     */
    public function invoices_per_client()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->report->invoices_per_client($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/invoices_per_client', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('invoices_per_client'), true);
        }

        $this->layout->buffer('content', 'reports/invoices_per_client_index')->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/reports/controllers/Reports.php
     * @legacy-function payment_history()
     */
    public function payment_history()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->report->payment_history($this->input->post('from_date'), $this->input->post('to_date')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/payment_history', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('payment_history'), true);
        }

        $this->layout->buffer('content', 'reports/payment_history_index')->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/reports/controllers/Reports.php
     * @legacy-function invoice_aging()
     */
    public function invoice_aging()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results' => $this->report->invoice_aging(),
            ];

            $html = $this->load->view('reports/invoice_aging', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('invoice_aging'), true);
        }

        $this->layout->buffer('content', 'reports/invoice_aging_index')->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/reports/controllers/Reports.php
     * @legacy-function sales_by_year()
     */
    public function sales_by_year()
    {
        if ($this->input->post('btn_submit')) {
            $data = [
                'results'   => $this->report->sales_by_year($this->input->post('from_date'), $this->input->post('to_date'), $this->input->post('minQuantity'), $this->input->post('maxQuantity'), $this->input->post('checkboxTax')),
                'from_date' => $this->input->post('from_date'),
                'to_date'   => $this->input->post('to_date'),
            ];

            $html = $this->load->view('reports/sales_by_year', $data, true);

            $this->load->helper('mpdf');

            pdf_create($html, trans('sales_by_date'), true);
        }

        $this->layout->buffer('content', 'reports/sales_by_year_index')->render();
    }
}
