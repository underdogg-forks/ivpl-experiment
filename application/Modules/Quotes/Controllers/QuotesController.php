<?php

namespace App\Modules\Quotes\Controllers;

use App\Core\AdminController;
use App\Libraries\CustomFieldService;
use App\Libraries\QuoteStatusFilterStrategy;
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
class QuotesController extends AdminController
{
    /**
     * Status filter strategy (Open/Closed Principle)
     */
    private QuoteStatusFilterStrategy $statusFilter;

    /**
     * Custom field service (Dependency Inversion Principle)
     */
    private CustomFieldService $customFieldService;

    /**
     * Quotes constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('quotes/quote');

        // Dependency Injection - SOLID principles
        $this->statusFilter = new QuoteStatusFilterStrategy();
        $this->customFieldService = new CustomFieldService();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function index()
     */
    public function index()
    {
        // Display all quotes by default
        redirect('quotes/status/all');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function status()
     */
    public function status(string $status = 'all', $page = 0)
    {
        // Apply status filter using Strategy Pattern (Open/Closed Principle)
        $this->statusFilter->apply($this->quote, $status);

        $this->quote->paginate(site_url('quotes/status/' . $status), $page);
        $quotes = $this->quote->result();

        $this->layout->set(
            [
                'quotes'             => $quotes,
                'status'             => $status,
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_quotes'),
                'filter_method'      => 'filter_quotes',
                'quote_statuses'     => $this->quote->statuses(),
            ]
        );

        $this->layout->buffer('content', 'quotes/index');
        $this->layout->render();
    }

    /**
     * @param $quote_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function view()
     */
    public function view($quote_id)
    {
        $this->load->model(
            [
                'quotes/mdl_quote_item',
                'tax_rates/mdl_tax_rate',
                'units/mdl_units',
                'mdl_quote_tax_rates',
                'custom_fields/mdl_custom_field',
                'custom_values/mdl_custom_value',
                'custom_fields/mdl_quote_custom',
                'upload/mdl_uploads',
            ]
        );

        $this->load->helper(['custom_values', 'dropzone', 'e-invoice']);

        $this->db->reset_query();

        $quote = $this->quote->get_by_id($quote_id);

        // Early return if quote not found (Early Return principle)
        if ( ! $quote) {
            show_404();
            return;
        }

        // Use CustomFieldService to load custom fields (DRY + Dynamic Programming)
        $customFieldData = $this->customFieldService->loadDocumentCustomFields(
            $this->quote,
            $this->quotecustom,
            $quote_id,
            'ip_quote_custom',
            'quote_custom_fieldid',
            'quote_custom_fieldvalue'
        );

        $items = $this->quoteitems->where('quote_id', $quote_id)->get()->result();

        // Get eInvoice library name and user checks
        $einvoice = get_einvoice_usage($quote, $items);

        // Check for multiple admin users with memoization (Dynamic Programming)
        $change_user = $this->hasMultipleAdminUsers();

        // Use SettingsCache for memoized settings (Dynamic Programming)
        $currencySettings = SettingsCache::getCurrencySettings();

        $this->layout->set(
            [
                'quote'           => $quote,
                'items'           => $items,
                'quote_id'        => $quote_id,
                'einvoice'        => $einvoice,
                'change_user'     => $change_user,
                'units'           => $this->unit->get()->result(),
                'tax_rates'       => $this->taxrates->get()->result(),
                'quote_tax_rates' => $this->quotetaxrates->where('quote_id', $quote_id)->get()->result(),
                'quote_statuses'  => $this->quote->statuses(),
                'custom_fields'   => $customFieldData['custom_fields'],
                'custom_values'   => $customFieldData['custom_values'],
                'custom_js_vars'  => [
                    'currency_symbol'           => $currencySettings['symbol'],
                    'currency_symbol_placement' => $currencySettings['placement'],
                    'decimal_point'             => $currencySettings['decimal_point'],
                ],
                'legacy_calculation' => config_item('legacy_calculation'),
            ]
        );

        $this->layout->buffer(
            [
                ['modal_delete_quote', 'quotes/modal_delete_quote'],
                ['modal_add_quote_tax', 'quotes/modal_add_quote_tax'],
                ['content', 'quotes/view'],
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
     * @param $quote_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function delete()
     */
    public function delete($quote_id)
    {
        // Delete the quote
        $this->quote->delete($quote_id);

        // Redirect to quote index
        redirect('quotes/index');
    }

    /**
     * @param      $quote_id
     * @param bool $stream
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function generate_pdf()
     */
    public function generate_pdf($quote_id, $stream = true, $quote_template = null)
    {
        $this->load->helper('pdf');

        // Use SettingsCache for memoized setting (Dynamic Programming)
        if (SettingsCache::isEnabled('mark_quotes_sent_pdf')) {
            $this->quote->generate_quote_number_if_applicable($quote_id);
            $this->quote->mark_sent($quote_id);
        }

        generate_quote_pdf($quote_id, $stream, $quote_template);
    }

    /**
     * @param $quote_id
     * @param $quote_tax_rate_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function delete_quote_tax()
     */
    public function delete_quote_tax(string $quote_id, $quote_tax_rate_id)
    {
        $this->load->model('quotes/quotetaxrate');
        $this->quotetaxrates->delete($quote_tax_rate_id);

        $this->load->model('quotes/quoteamount');
        $global_discount['item'] = $this->quoteamounts->get_global_discount($quote_id);
        // Recalculate quote amounts
        $this->quoteamounts->calculate($quote_id, $global_discount);

        redirect('quotes/view/' . $quote_id);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Quotes.php
     * @legacy-function recalculate_all_quotes()
     */
    public function recalculate_all_quotes()
    {
        $this->db->select('quote_id');
        $quote_ids = $this->db->get('ip_quotes')->result();

        $this->load->model('quotes/quoteamount');

        foreach ($quote_ids as $quote_id) {
            $global_discount['item'] = $this->quoteamounts->get_global_discount($quote_id->quote_id);
            // Recalculate quote amounts
            $this->quoteamounts->calculate($quote_id->quote_id, $global_discount);
        }
    }
}
