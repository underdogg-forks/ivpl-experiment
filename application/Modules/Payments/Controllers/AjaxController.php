<?php

namespace App\Modules\Payments\Controllers;

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
class PaymentsAjaxController extends AdminController
{
    public $ajax_controller = true;

    public function add()
    {
        $this->load->model('payments/payment');

        if ($this->payment->run_validation()) {
            $payment_id = $this->payment->save();

            $response = [
                'success'    => 1,
                'payment_id' => $payment_id,
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        echo json_encode($response);
    }

    public function modal_add_payment()
    {
        $this->load->module('layout');
        $this->load->model('payments/payment');
        $this->load->model('payment_methods/paymentmethods');
        $this->load->model('custom_fields/paymentcustom');

        $data = [
            'payment_methods'        => $this->paymentmethods->get()->result(),
            'invoice_id'             => $this->security->xss_clean($this->input->post('invoice_id')),
            'invoice_balance'        => $this->input->post('invoice_balance'),
            'invoice_payment_method' => $this->input->post('invoice_payment_method'),
            'payment_cf_exist'       => $this->security->xss_clean($this->input->post('payment_cf_exist')),
        ];

        $this->layout->load_view('payments/modal_add_payment', $data);
    }
}
