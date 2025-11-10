<?php

namespace App\Modules\Invoice_groups\Controllers;

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
class Invoice_Groups extends \Admin_Controller
{
    /**
     * Invoice_Groups constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoice_groups/invoicegroups');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->invoicegroups->paginate(site_url('invoice_groups/index'), $page);
        $invoice_groups = $this->invoicegroups->result();

        $this->layout->set('invoice_groups', $invoice_groups);
        $this->layout->buffer('content', 'invoice_groups/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('invoice_groups');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->invoicegroups->run_validation()) {
            $this->invoicegroups->save($id);
            redirect('invoice_groups');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->invoicegroups->prep_form($id)) {
                show_404();
            }
        } elseif ( ! $id) {
            $this->invoicegroups->set_form_value('invoice_group_left_pad', 0);
            $this->invoicegroups->set_form_value('invoice_group_next_id', 1);
        }

        $this->layout->buffer('content', 'invoice_groups/form');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->invoicegroups->delete($id);
        redirect('invoice_groups');
    }
}
