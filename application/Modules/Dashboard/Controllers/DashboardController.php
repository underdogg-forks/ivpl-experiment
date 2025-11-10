<?php

namespace App\Modules\Dashboard\Controllers;

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
class DashboardController extends AdminController
{
    /**
     * Legacy migration info:
     * @legacy-file application/modules/dashboard/controllers/Dashboard.php
     * @legacy-function index()
     */
    public function index()
    {
        $this->load->model('invoices/invoiceamount');
        $this->load->model('quotes/quoteamount');
        $this->load->model('invoices/invoice');
        $this->load->model('quotes/quote');
        $this->load->model('projects/project');
        $this->load->model('tasks/task');

        $quote_overview_period   = get_setting('quote_overview_period');
        $invoice_overview_period = get_setting('invoice_overview_period');

        $this->layout->set(
            [
                'invoice_status_totals' => $this->invoiceamounts->get_status_totals($invoice_overview_period),
                'quote_status_totals'   => $this->quoteamounts->get_status_totals($quote_overview_period),
                'invoice_status_period' => str_replace('-', '_', $invoice_overview_period),
                'quote_status_period'   => str_replace('-', '_', $quote_overview_period),
                'invoices'              => $this->invoice->limit(10)->get()->result(),
                'quotes'                => $this->quote->limit(10)->get()->result(),
                'invoice_statuses'      => $this->invoice->statuses(),
                'quote_statuses'        => $this->quote->statuses(),
                'overdue_invoices'      => $this->invoice->is_overdue()->get()->result(),
                'projects'              => $this->project->get_latest()->get()->result(),
                'tasks'                 => $this->task->get_latest()->get()->result(),
                'task_statuses'         => $this->task->statuses(),
            ]
        );

        $this->layout->buffer('content', 'dashboard/index');
        $this->layout->render();
    }
}
