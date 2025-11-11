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
class InvoicesController extends AdminController
{
    /**
     * Invoices constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoices/invoice');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function index()
     */
    public function index(): void
    {
        // Display all invoices by default
        redirect('invoices/status/all');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function status()
     */
    public function status(string $status = 'all', $page = 0): void
    {
        // Determine which group of invoices to load
        switch ($status) {
            case 'draft':
                $this->invoice->is_draft();
                break;
            case 'sent':
                $this->invoice->is_sent();
                break;
            case 'viewed':
                $this->invoice->is_viewed();
                break;
            case 'paid':
                $this->invoice->is_paid();
                break;
            case 'overdue':
                $this->invoice->is_overdue();
                break;
        }

        $this->invoice->paginate(site_url('invoices/status/' . $status), $page);
        $invoices = $this->invoice->result();

        $this->layout->set(
            [
                'invoices'           => $invoices,
                'status'             => $status,
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_invoices'),
                'filter_method'      => 'filter_invoices',
                'invoice_statuses'   => $this->invoice->statuses(),
            ]
        );

        $this->layout->buffer('content', 'invoices/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function archive()
     */
    public function archive(): void
    {
        $invoice_array = $this->invoice->get_archives(0);
        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_archives'),
                'filter_method'      => 'filter_archives',
                'invoices_archive'   => $invoice_array,
            ]
        );
        $this->layout->buffer('content', 'invoices/archive');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function download()
     */
    public function download($invoice): void
    {
        $safeBaseDir = realpath(uploads_archive_path());

        $fileName = urldecode(basename($invoice)); // Strip directory traversal sequences
        $filePath = realpath($safeBaseDir . DIRECTORY_SEPARATOR . $fileName);

        if ($filePath === false || ! str_starts_with($filePath, $safeBaseDir)) {
            log_message('error', 'Invalid file access attempt: ' . $fileName);
            show_404();

            return;
        }

        if ( ! file_exists($filePath)) {
            log_message('error', 'While downloading: File not found: ' . $filePath);
            show_404();

            return;
        }

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function view()
     */
    public function view($invoice_id): void
    {
        $this->load->model(
            [
                'invoices/mdl_item',
                'invoices/mdl_invoice_tax_rate',
                'tax_rates/mdl_tax_rate',
                'payment_methods/mdl_payment_method',
                'custom_fields/mdl_custom_field',
                'custom_values/mdl_custom_value',
                'custom_fields/mdl_invoice_custom',
                'units/mdl_units',
                'upload/mdl_uploads',
            ]
        );
        $this->load->helper(['custom_values', 'dropzone', 'e-invoice']);
        $this->load->module('payments');

        $this->db->reset_query();

        /*$invoice_custom = $this->invoicecustom->where('invoice_id', $invoice_id)->get();

        if ($invoice_custom->num_rows()) {
            $invoice_custom = $invoice_custom->row();

            unset($invoice_custom->invoice_id, $invoice_custom->invoice_custom_id);

            foreach ($invoice_custom as $key => $val) {
                $this->invoice->set_form_value('custom[' . $key . ']', $val);
            }
        }*/

        $fields  = $this->invoicecustom->by_id($invoice_id)->get()->result();
        $invoice = $this->invoice->get_by_id($invoice_id);

        if ( ! $invoice) {
            show_404();
        }

        $custom_fields = $this->customfields->by_table('ip_invoice_custom')->get()->result();
        $custom_values = [];
        foreach ($custom_fields as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->customvalues->custom_value_fields())) {
                $values                                        = $this->customvalues->get_by_fid($custom_field->custom_field_id)->result();
                $custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        foreach ($custom_fields as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->invoice_custom_fieldid == $cfield->custom_field_id) {
                    // TODO: Hackish, may need a better optimization
                    $this->invoice->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->invoice_custom_fieldvalue
                    );
                    break;
                }
            }
        }

        // Check whether there are payment custom fields
        $payment_cf       = $this->customfields->by_table('ip_payment_custom')->get();
        $payment_cf_exist = ($payment_cf->num_rows() > 0) ? 'yes' : 'no';
        // Get Items
        $items = $this->item->where('invoice_id', $invoice_id)->get()->result();
        // Get eInvoice library name and user checks
        $einvoice = get_einvoice_usage($invoice, $items);
        // Activate 'Change_user' if admin users > 1  (get the sum of user type = 1 & active)
        $change_user = $this->db->from('ip_users')->where(['user_type' => 1, 'user_active' => 1])->select_sum('user_type')->get()->row();
        $change_user = $change_user->user_type > 1;

        $this->layout->set(
            [
                'invoice'           => $invoice,
                'items'             => $items,
                'invoice_id'        => $invoice_id,
                'einvoice'          => $einvoice,
                'change_user'       => $change_user,
                'tax_rates'         => $this->taxrates->get()->result(),
                'invoice_tax_rates' => $this->invoicetaxrates->where('invoice_id', $invoice_id)->get()->result(),
                'units'             => $this->unit->get()->result(),
                'payment_methods'   => $this->paymentmethods->get()->result(),
                'custom_fields'     => $custom_fields,
                'custom_values'     => $custom_values,
                'custom_js_vars'    => [
                    'currency_symbol'           => get_setting('currency_symbol'),
                    'currency_symbol_placement' => get_setting('currency_symbol_placement'),
                    'decimal_point'             => get_setting('decimal_point'),
                ],
                'invoice_statuses'   => $this->invoice->statuses(),
                'payment_cf_exist'   => $payment_cf_exist,
                'legacy_calculation' => config_item('legacy_calculation'),
            ]
        );

        $this->layout->buffer(
            [
                ['modal_delete_invoice', 'invoices/modal_delete_invoice'],
                ['modal_add_invoice_tax', 'invoices/modal_add_invoice_tax'],
                ['modal_add_payment', 'payments/modal_add_payment'],
                ['content', 'invoices/view' . ($invoice->sumex_id ? '_sumex' : '')],
            ]
        );

        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function delete()
     */
    public function delete($invoice_id): void
    {
        // Get the status of the invoice
        $invoice        = $this->invoice->get_by_id($invoice_id);
        $invoice_status = $invoice->invoice_status_id;

        if ($invoice_status == 1 || $this->config->item('enable_invoice_deletion') === true) {
            // If invoice refers to tasks, mark those tasks back to 'Complete'
            $this->load->model('tasks/task');
            $tasks = $this->task->update_on_invoice_delete($invoice_id);

            // Delete the invoice
            $this->invoice->delete($invoice_id);
        } else {
            // Add alert that invoices can't be deleted
            $this->session->set_flashdata('alert_error', trans('invoice_deletion_forbidden'));
        }

        // Redirect to invoice index
        redirect('invoices/index');
    }

    /**
     * @param      $invoice_id
     * @param bool $stream
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function generate_pdf()
     */
    public function generate_pdf($invoice_id, $stream = true, $invoice_template = null): void
    {
        $this->load->helper('pdf');

        if (get_setting('mark_invoices_sent_pdf') == 1) {
            $this->invoice->generate_invoice_number_if_applicable($invoice_id);
            $this->invoice->mark_sent($invoice_id);
        }

        generate_invoice_pdf($invoice_id, $stream, $invoice_template, null);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function generate_xml()
     */
    public function generate_xml($invoice_id): void
    {
        $invoice = $this->invoice->get_by_id($invoice_id);
        if ( ! $invoice) {
            show_404();
        }

        $this->load->model('invoices/item');
        $items = $this->item->where('invoice_id', $invoice_id)->get()->result();

        $this->load->helper('e-invoice'); // eInvoicing++
        $einvoice = get_einvoice_usage($invoice, $items, false);
        if ( ! $einvoice->user) {
            show_404();
        }

        // eInvoice library to Generate the appropriate UBL/CII or false
        $xml_id    = $einvoice->name; // $invoice->client_einvoicing_version
        $options   = [];
        $generator = $xml_id;
        $path      = APPPATH . 'helpers/XMLconfigs/';
        if ($xml_id && file_exists($path . $xml_id . '.php') && include $path . $xml_id . '.php') {
            $embed_xml = $xml_setting['embedXML'];
            $XMLname   = $xml_setting['XMLname'];
            $options   = (empty($xml_setting['options']) ? $options : $xml_setting['options']); // Optional
            $generator = (empty($xml_setting['generator']) ? $generator : $xml_setting['generator']); // Optional
        }

        $filename = trans('invoice') . '_' . str_replace(['\\', '/'], '_', $invoice->invoice_number);
        $path     = generate_xml_invoice_file($invoice, $items, $generator, $filename, $options);
        $this->output->set_content_type('text/xml');
        $this->output->set_output(file_get_contents($path));
        unlink($path);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function generate_sumex_pdf()
     */
    public function generate_sumex_pdf($invoice_id): void
    {
        $this->load->helper('pdf');

        generate_invoice_sumex($invoice_id);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function generate_sumex_copy()
     */
    public function generate_sumex_copy($invoice_id): void
    {
        $this->load->model('invoices/item');
        $this->load->library('Sumex', [
            'invoice' => $this->invoice->get_by_id($invoice_id),
            'items'   => $this->item->where('invoice_id', $invoice_id)->get()->result(),
            'options' => [
                'copy'   => '1',
                'storno' => '0',
            ],
        ]);

        $this->output->set_content_type('application/pdf');
        $this->output->set_output($this->sumex->pdf());
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function delete_invoice_tax()
     */
    public function delete_invoice_tax(string $invoice_id, $invoice_tax_rate_id): void
    {
        $this->load->model('invoices/invoicetaxrate');
        $this->invoicetaxrates->delete($invoice_tax_rate_id);

        $this->load->model('invoices/invoiceamount');
        $global_discount['item'] = $this->invoiceamounts->get_global_discount($invoice_id);
        // Recalculate invoice amounts
        $this->invoiceamounts->calculate($invoice_id, $global_discount);

        redirect('invoices/view/' . $invoice_id);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function recalculate_all_invoices()
     */
    public function recalculate_all_invoices(): void
    {
        $this->db->select('invoice_id');
        $invoice_ids = $this->db->get('ip_invoices')->result();

        $this->load->model('invoices/invoiceamount');

        foreach ($invoice_ids as $invoice_id) {
            $global_discount['item'] = $this->invoiceamounts->get_global_discount($invoice_id->invoice_id);
            // Recalculate invoice amounts
            $this->invoiceamounts->calculate($invoice_id->invoice_id, $global_discount);
        }
    }
}
