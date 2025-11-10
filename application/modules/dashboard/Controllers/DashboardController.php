<?php

namespace App\Modules\Dashboard\Controllers;

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
class DashboardController extends \Admin_Controller
{
    public function index()
    {
        $this->load->model('invoices/invoiceamounts');
        $this->load->model('quotes/quoteamounts');
        $this->load->model('invoices/invoices');
        $this->load->model('quotes/quotes');
        $this->load->model('projects/projects');
        $this->load->model('tasks/tasks');

        $quote_overview_period   = get_setting('quote_overview_period');
        $invoice_overview_period = get_setting('invoice_overview_period');

        $this->layout->set(
            [
                'invoice_status_totals' => $this->invoiceamounts->get_status_totals($invoice_overview_period),
                'quote_status_totals'   => $this->quoteamounts->get_status_totals($quote_overview_period),
                'invoice_status_period' => str_replace('-', '_', $invoice_overview_period),
                'quote_status_period'   => str_replace('-', '_', $quote_overview_period),
                'invoices'              => $this->invoices->limit(10)->get()->result(),
                'quotes'                => $this->quotes->limit(10)->get()->result(),
                'invoice_statuses'      => $this->invoices->statuses(),
                'quote_statuses'        => $this->quotes->statuses(),
                'overdue_invoices'      => $this->invoices->is_overdue()->get()->result(),
                'projects'              => $this->projects->get_latest()->get()->result(),
                'tasks'                 => $this->tasks->get_latest()->get()->result(),
                'task_statuses'         => $this->tasks->statuses(),
            ]
        );

        $this->layout->buffer('content', 'dashboard/index');
        $this->layout->render();
    }
}
