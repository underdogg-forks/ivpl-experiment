<?php

namespace App\Modules\PaymentMethods\Controllers;

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
class Payment_Methods extends AdminController
{
    /**
     * Payment_Methods constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('payment_methods/paymentmethods');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/controllers/PaymentMethods.php
     * @legacy-function index()
     */
    public function index($page = 0)
    {
        $this->paymentmethods->paginate(site_url('payment_methods/index'), $page);
        $payment_methods = $this->paymentmethods->result();

        $this->layout->set('payment_methods', $payment_methods);
        $this->layout->buffer('content', 'payment_methods/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/controllers/PaymentMethods.php
     * @legacy-function form()
     */
    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('payment_methods');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->input->post('is_update') == 0 && $this->input->post('payment_method_name') != '') {
            $check = $this->db->get_where('ip_payment_methods', ['payment_method_name' => $this->input->post('payment_method_name')])->result();
            if ( ! empty($check)) {
                $this->session->set_flashdata('alert_error', trans('payment_method_already_exists'));
                redirect('payment_methods/form');
            }
        }

        if ($this->paymentmethods->run_validation()) {
            $this->paymentmethods->save($id);
            redirect('payment_methods');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->paymentmethods->prep_form($id)) {
                show_404();
            }

            $this->paymentmethods->set_form_value('is_update', true);
        }

        $this->layout->buffer('content', 'payment_methods/form');
        $this->layout->render();
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/payment_methods/controllers/PaymentMethods.php
     * @legacy-function delete()
     */
    public function delete($id)
    {
        $this->paymentmethods->delete($id);
        redirect('payment_methods');
    }
}
