<?php

namespace App\Modules\Invoices\Controllers;

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
class InvoicesController extends \Admin_Controller
{
    /**
     * InvoicesController constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoices/mdl_invoices');
    }

    public function index(): void
    {
        // Display all invoices by default
        redirect('invoices/status/all');
    }

    /**
     * @param int $page
     */
    public function status(string $status = 'all', $page = 0): void
    {
        // Determine which group of invoices to load
        switch ($status) {
            case 'draft':
                $this->mdl_invoices->is_draft();
                break;
            case 'sent':
                $this->mdl_invoices->is_sent();
                break;
            case 'viewed':
                $this->mdl_invoices->is_viewed();
                break;
            case 'paid':
                $this->mdl_invoices->is_paid();
                break;
            case 'overdue':
                $this->mdl_invoices->is_overdue();
                break;
        }

        $this->mdl_invoices->paginate(site_url('invoices/status/' . $status), $page);
        $invoices = $this->mdl_invoices->result();

        $this->layout->set(
            [
                'invoices'           => $invoices,
                'status'             => $status,
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_invoices'),
                'filter_method'      => 'filter_invoices',
                'invoice_statuses'   => $this->mdl_invoices->statuses(),
            ]
        );

        $this->layout->buffer('content', 'invoices/index');
        $this->layout->render();
    }

    /**
     * Invoice form for creating/editing invoices
     */
    public function form($id = null): void
    {
        if ($this->input->post('btn_cancel')) {
            redirect('invoices');
        }

        // Load required models
        $this->load->model([
            'invoices/mdl_items',
            'invoices/mdl_invoice_tax_rates',
            'tax_rates/mdl_tax_rates',
            'clients/mdl_clients',
            'invoice_groups/mdl_invoice_groups',
            'custom_fields/mdl_custom_fields',
            'custom_fields/mdl_invoice_custom',
        ]);

        // Process form submission
        if ($this->input->post('btn_submit')) {
            $this->filter_input();

            if ($this->mdl_invoices->run_validation()) {
                $id = $this->mdl_invoices->save($id);

                // Save custom fields if they exist
                if ($this->input->post('custom')) {
                    $this->mdl_invoice_custom->save_custom($id, $this->input->post('custom'));
                }

                $this->session->set_flashdata('alert_success', trans('invoice_saved'));
                redirect('invoices/view/' . $id);
            }
        }

        // Load invoice data if editing
        if ($id) {
            $invoice = $this->mdl_invoices->get_by_id($id);
            
            if (!$invoice) {
                show_404();
                return;
            }

            // Load invoice items
            $items = $this->mdl_items->where('invoice_id', $id)->get()->result();
            
            // Load custom fields
            $invoice_custom = $this->mdl_invoice_custom->where('invoice_id', $id)->get();
            if ($invoice_custom->num_rows()) {
                $invoice_custom = $invoice_custom->row();
                unset($invoice_custom->invoice_id, $invoice_custom->invoice_custom_id);
                
                foreach ($invoice_custom as $key => $val) {
                    $this->mdl_invoices->set_form_value('custom[' . $key . ']', $val);
                }
            }
        } else {
            $invoice = null;
            $items = [];
        }

        // Prepare form data
        $this->layout->set([
            'invoice' => $invoice,
            'items' => $items,
            'clients' => $this->mdl_clients->get()->result(),
            'invoice_groups' => $this->mdl_invoice_groups->get()->result(),
            'tax_rates' => $this->mdl_tax_rates->get()->result(),
            'custom_fields' => $this->mdl_custom_fields->by_table('ip_invoices')->get()->result(),
        ]);

        $this->layout->buffer('content', 'invoices/form');
        $this->layout->render();
    }

    public function archive(): void
    {
        $invoice_array = $this->mdl_invoices->get_archives(0);
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

    public function download($invoice): void
    {
        $safeBaseDir = realpath(UPLOADS_ARCHIVE_FOLDER);

        $fileName = urldecode(basename($invoice)); // Strip directory traversal sequences
        $filePath = realpath($safeBaseDir . DIRECTORY_SEPARATOR . $fileName);

        if ($filePath === false || ! str_starts_with($filePath, $safeBaseDir)) {
            log_message('error', 'Invalid file access attempt: ' . $fileName);
            show_404();

            return;
        }

        if (! file_exists($filePath)) {
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

    public function view($invoice_id): void
    {
        $this->load->model(
            [
                'invoices/mdl_items',
                'invoices/mdl_invoice_tax_rates',
                'tax_rates/mdl_tax_rates',
                'payment_methods/mdl_payment_methods',
                'custom_fields/mdl_custom_fields',
                'custom_values/mdl_custom_values',
                'custom_fields/mdl_invoice_custom',
                'units/mdl_units',
                'upload/mdl_uploads',
            ]
        );
        $this->load->helper(['custom_values', 'dropzone', 'e-invoice']);
        $this->load->module('payments');

        $this->db->reset_query();

        $invoice = $this->mdl_invoices->get_by_id($invoice_id);

        if (! $invoice) {
            show_404();

            return;
        }

        $this->layout->set(
            [
                'invoice'         => $invoice,
                'items'           => $this->mdl_items->where('invoice_id', $invoice_id)->get()->result(),
                'invoice_taxes'   => $this->mdl_invoice_tax_rates->where('invoice_id', $invoice_id)->get()->result(),
                'tax_rates'       => $this->mdl_tax_rates->get()->result(),
                'payment_methods' => $this->mdl_payment_methods->get()->result(),
                'custom_fields'   => $this->mdl_custom_fields->by_table('ip_invoices')->get()->result(),
                'custom_values'   => $this->mdl_custom_values->get()->result(),
                'uploads'         => $this->mdl_uploads->where('invoice_id', $invoice_id)->get()->result(),
            ]
        );

        $this->layout->buffer(
            [
                'modal_delete_invoice', 'invoices/modal_delete_invoice',
                'modal_add_invoice_tax', 'invoices/modal_add_invoice_tax',
                'modal_add_payment', 'payments/modal_add_payment',
                'content', 'invoices/view',
            ]
        );
        $this->layout->render();
    }

    public function delete($invoice_id): void
    {
        $invoice = $this->mdl_invoices->get_by_id($invoice_id);

        if (! $invoice) {
            show_404();
            return;
        }

        // Check if deletion is allowed
        if (! get_setting('enable_invoice_deletion') && $invoice->invoice_status_id != 1) {
            $this->session->set_flashdata('alert_error', trans('invoice_deletion_forbidden'));
            redirect('invoices/view/' . $invoice_id);
            return;
        }

        $this->mdl_invoices->delete($invoice_id);

        $this->session->set_flashdata('alert_success', trans('invoice_deleted'));
        redirect('invoices');
    }

    public function generate_pdf($invoice_id, $stream = true, $invoice_template = null): void
    {
        $this->load->helper('pdf');

        generate_invoice_pdf($invoice_id, $stream, $invoice_template, null);
    }

    public function generate_xml($invoice_id): void
    {
        $this->load->model('invoices/mdl_items');
        $this->load->helper('xml');

        $invoice = $this->mdl_invoices->get_by_id($invoice_id);

        if (! $invoice) {
            show_404();
            return;
        }

        $items = $this->mdl_items->where('invoice_id', $invoice_id)->get()->result();

        $xml_string = generate_invoice_xml($invoice, $items);

        header('Content-Type: application/xml');
        header('Content-Disposition: attachment; filename="invoice_' . $invoice->invoice_number . '.xml"');
        echo $xml_string;
        exit;
    }

    public function generate_sumex_pdf($invoice_id): void
    {
        $this->load->helper('pdf');
        generate_invoice_sumex($invoice_id);
    }

    public function generate_sumex_copy($invoice_id): void
    {
        $this->load->helper('pdf');
        $invoice = $this->mdl_invoices->get_by_id($invoice_id);

        if (! $invoice) {
            show_404();
            return;
        }

        generate_invoice_sumex($invoice_id, true, 'F', $invoice->invoice_number);
    }

    public function delete_invoice_tax(string $invoice_id, $invoice_tax_rate_id): void
    {
        $this->load->model('invoices/mdl_invoice_tax_rates');
        $this->mdl_invoice_tax_rates->delete($invoice_tax_rate_id);

        redirect('invoices/view/' . $invoice_id);
    }

    public function recalculate_all_invoices(): void
    {
        $this->load->model('invoices/mdl_invoice_amounts');
        $this->mdl_invoice_amounts->calculate_all();

        $this->session->set_flashdata('alert_success', trans('all_invoices_recalculated'));
        redirect('invoices');
    }
}
