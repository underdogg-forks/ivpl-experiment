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
class PaymentsController extends AdminController
{
    /**
     * Payments constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('payments/payment');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->payment->paginate(site_url('payments/index'), $page);
        $payments = $this->payment->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_payments'),
                'filter_method'      => 'filter_payments',
                'payments'           => $payments,
            ]
        );

        $this->layout->buffer('content', 'payments/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('payments');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        $this->load->model('custom_fields/paymentcustom');

        if ($this->payment->run_validation()) {
            $id = $this->payment->save($id);

            $this->paymentcustom->save_custom($id, $this->input->post('custom'));

            redirect('payments');
        }

        if ( ! $this->input->post('btn_submit')) {
            $prep_form = $this->payment->prep_form($id);
            if ($id && ! $prep_form) {
                show_404();
            }

            $this->load->model('custom_values/customvalues');
            $payment_custom = $this->paymentcustom->where('payment_id', $id)->get();
            if ($payment_custom->num_rows()) {
                $payment_custom = $payment_custom->row();

                unset($payment_custom->payment_id, $payment_custom->payment_custom_id);

                foreach ($payment_custom as $key => $val) {
                    $this->payment->set_form_value('custom[' . $key . ']', $val);
                }
            }
        } elseif ($this->input->post('custom')) {
            foreach ($this->input->post('custom') as $key => $val) {
                $this->payment->set_form_value('custom[' . $key . ']', $val);
            }
        }

        $this->load->helper('custom_values');
        $this->load->model([
            'invoices/mdl_invoices',
            'payment_methods/mdl_payment_methods',
            'custom_fields/mdl_custom_fields',
            'custom_values/mdl_custom_values',
        ]);

        $open_invoices = $this->invoice->is_open()->get()->result();

        $custom_fields = $this->customfields->by_table('ip_payment_custom')->get()->result();
        $custom_values = [];

        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->customvalues->custom_value_fields())) {
                $values                                        = $this->customvalues->get_by_fid($custom_field->custom_field_id)->result();
                $custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        $fields = $this->paymentcustom->get_by_payid($id);

        foreach ($custom_fields as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->payment_custom_fieldid == $cfield->custom_field_id) {
                    // TODO: Hackish, may need a better optimization
                    $this->payment->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->payment_custom_fieldvalue
                    );
                    break;
                }
            }
        }

        $amounts                 = [];
        $invoice_payment_methods = [];
        foreach ($open_invoices as $open_invoice) {
            $amounts['invoice' . $open_invoice->invoice_id]                 = format_amount($open_invoice->invoice_balance);
            $invoice_payment_methods['invoice' . $open_invoice->invoice_id] = $open_invoice->payment_method;
        }

        $this->layout->set(
            [
                'payment_id'              => $id,
                'payment_methods'         => $this->paymentmethods->get()->result(),
                'open_invoices'           => $open_invoices,
                'custom_fields'           => $custom_fields,
                'custom_values'           => $custom_values,
                'amounts'                 => json_encode($amounts),
                'invoice_payment_methods' => json_encode($invoice_payment_methods),
            ]
        );

        if ($id) {
            $this->layout->set('payment', $this->payment->where('ip_payments.payment_id', $id)->get()->row());
        }

        $this->layout->buffer('content', 'payments/form');
        $this->layout->render();
    }

    /**
     * @param int $page
     */
    public function online_logs($page = 0)
    {
        $this->load->model('payments/paymentlogs');

        $this->paymentlogs->paginate(site_url('payments/online_logs'), $page);
        $payment_logs = $this->paymentlogs->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_online_logs'),
                'filter_method'      => 'filter_online_logs',
                'payment_logs'       => $payment_logs,
            ]
        );

        $this->layout->buffer('content', 'payments/online_logs');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->payment->delete($id);
        redirect('payments');
    }
}
