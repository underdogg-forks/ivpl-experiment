<?php

namespace App\Modules\Invoices\Controllers;

use App\Core\AdminController;
use App\Libraries\CustomFieldService;
use App\Libraries\InvoiceStatusFilterStrategy;
use App\Libraries\SettingsCache;

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
     * Status filter strategy (Open/Closed Principle)
     */
    private InvoiceStatusFilterStrategy $statusFilter;

    /**
     * Custom field service (Dependency Inversion Principle)
     */
    private CustomFieldService $customFieldService;

    /**
     * Invoices constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('invoices/invoice');

        // Dependency Injection - SOLID principles
        $this->statusFilter = new InvoiceStatusFilterStrategy();
        $this->customFieldService = new CustomFieldService();
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
        // Apply status filter using Strategy Pattern (Open/Closed Principle)
        $this->statusFilter->apply($this->invoice, $status);

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

        // Early return for invalid base directory (Early Return principle)
        if ($safeBaseDir === false) {
            log_message('error', 'Invalid archive path configuration');
            show_404();
            return;
        }

        $fileName = urldecode(basename($invoice)); // Strip directory traversal sequences
        $filePath = realpath($safeBaseDir . DIRECTORY_SEPARATOR . $fileName);

        // Early return for invalid file path (Early Return principle)
        if ($filePath === false || ! str_starts_with($filePath, $safeBaseDir)) {
            log_message('error', 'Invalid file access attempt: ' . $fileName);
            show_404();
            return;
        }

        // Early return for non-existent file (Early Return principle)
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

        $invoice = $this->invoice->get_by_id($invoice_id);

        // Early return if invoice not found (Early Return principle)
        if ( ! $invoice) {
            show_404();
            return;
        }

        // Use CustomFieldService to load custom fields (DRY + Dynamic Programming)
        $customFieldData = $this->customFieldService->loadDocumentCustomFields(
            $this->invoice,
            $this->invoicecustom,
            $invoice_id,
            'ip_invoice_custom',
            'invoice_custom_fieldid',
            'invoice_custom_fieldvalue'
        );

        // Check whether there are payment custom fields
        $payment_cf       = $this->customfields->by_table('ip_payment_custom')->get();
        $payment_cf_exist = ($payment_cf->num_rows() > 0) ? 'yes' : 'no';

        // Get Items
        $items = $this->item->where('invoice_id', $invoice_id)->get()->result();

        // Get eInvoice library name and user checks
        $einvoice = get_einvoice_usage($invoice, $items);

        // Check for multiple admin users with memoization (Dynamic Programming)
        $change_user = $this->hasMultipleAdminUsers();

        // Use SettingsCache for memoized settings (Dynamic Programming)
        $currencySettings = SettingsCache::getCurrencySettings();

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
                'custom_fields'     => $customFieldData['custom_fields'],
                'custom_values'     => $customFieldData['custom_values'],
                'custom_js_vars'    => [
                    'currency_symbol'           => $currencySettings['symbol'],
                    'currency_symbol_placement' => $currencySettings['placement'],
                    'decimal_point'             => $currencySettings['decimal_point'],
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
     * Check if multiple admin users exist
     * Extracted common logic with memoization (DRY + Dynamic Programming)
     */
    private function hasMultipleAdminUsers(): bool
    {
        static $multipleAdmins = null;

        // Dynamic Programming: Return cached result if available
        if ($multipleAdmins !== null) {
            return $multipleAdmins;
        }

        $result = $this->db
            ->from('ip_users')
            ->where(['user_type' => 1, 'user_active' => 1])
            ->select_sum('user_type')
            ->get()
            ->row();

        $multipleAdmins = ($result->user_type ?? 0) > 1;

        return $multipleAdmins;
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Invoices.php
     * @legacy-function delete()
     */
    public function delete($invoice_id): void
    {
        // Get the status of the invoice
        $invoice = $this->invoice->get_by_id($invoice_id);

        // Early return if invoice not found (Early Return principle)
        if (!$invoice) {
            $this->session->set_flashdata('alert_error', trans('invoice_not_found'));
            redirect('invoices/index');
            return;
        }

        $invoice_status = $invoice->invoice_status_id;
        $canDelete = ($invoice_status == 1) || 
                     SettingsCache::isEnabled('enable_invoice_deletion');

        // Early return if deletion not allowed (Early Return principle)
        if (!$canDelete) {
            $this->session->set_flashdata('alert_error', trans('invoice_deletion_forbidden'));
            redirect('invoices/index');
            return;
        }

        // If invoice refers to tasks, mark those tasks back to 'Complete'
        $this->load->model('tasks/task');
        $this->task->update_on_invoice_delete($invoice_id);

        // Delete the invoice
        $this->invoice->delete($invoice_id);

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

        // Use SettingsCache for memoized setting (Dynamic Programming)
        if (SettingsCache::isEnabled('mark_invoices_sent_pdf')) {
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

        // Early return if invoice not found (Early Return principle)
        if ( ! $invoice) {
            show_404();
            return;
        }

        $this->load->model('invoices/item');
        $items = $this->item->where('invoice_id', $invoice_id)->get()->result();

        $this->load->helper('e-invoice'); // eInvoicing++
        $einvoice = get_einvoice_usage($invoice, $items, false);

        // Early return if no einvoice user (Early Return principle)
        if ( ! $einvoice->user) {
            show_404();
            return;
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
