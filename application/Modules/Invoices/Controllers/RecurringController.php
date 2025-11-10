<?php

namespace App\Modules\Invoices\Controllers;

use App\Core\AdminController;

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
class RecurringController extends AdminController
{
    /**
     * Recurring constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoices/invoicesrecurring');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Recurring.php
     * @legacy-function index()
     */
    public function index($page = 0)
    {
        $this->invoices_recurring->paginate(site_url('invoices/recurring'), $page);
        $recurring_invoices = $this->invoices_recurring->result();

        $this->layout->set([
            'filter_display'     => true,
            'filter_placeholder' => trans('filter_invoices_recuring'),
            'filter_method'      => 'filter_invoices_recuring',
            'recur_frequencies'  => $this->invoices_recurring->recur_frequencies,
            'recurring_invoices' => $recurring_invoices,
        ]);
        $this->layout->buffer('content', 'invoices/index_recurring');
        $this->layout->render();
    }

    /**
     * @param $invoice_recurring_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Recurring.php
     * @legacy-function stop()
     */
    public function stop($invoice_recurring_id)
    {
        $this->invoices_recurring->stop($invoice_recurring_id);
        redirect('invoices/recurring/index');
    }

    /**
     * @param $invoice_recurring_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Recurring.php
     * @legacy-function delete()
     */
    public function delete($invoice_recurring_id)
    {
        $this->invoices_recurring->delete($invoice_recurring_id);
        redirect('invoices/recurring/index');
    }
}
