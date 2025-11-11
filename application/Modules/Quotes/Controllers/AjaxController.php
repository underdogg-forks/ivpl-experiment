<?php

namespace App\Modules\Quotes\Controllers;

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
class QuotesAjaxController extends AdminController
{
    public $ajax_controller = true;

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function save()
     */
    public function save()
    {
        $this->load->model([
            'quotes/mdl_quote_item',
            'quotes/mdl_quotes',
            'units/mdl_units',
        ]);

        $quote_id = $this->security->xss_clean($this->input->post('quote_id', true));

        $this->quote->set_id($quote_id);

        if ($this->quote->run_validation('validation_rules_save_quote')) {
            $items = json_decode($this->input->post('items'));

            $quote_discount_percent = (float) $this->input->post('quote_discount_percent');
            $quote_discount_amount  = (float) $this->input->post('quote_discount_amount');

            // Percent by default. Only one allowed. Prevent set 2 global discounts by geeky client - since v1.6.3
            if ($quote_discount_percent && $quote_discount_amount) {
                $quote_discount_amount = 0.0;
            }

            // New discounts (for legacy_calculation false) - since v1.6.3 Need if taxes applied after discounts
            $items_subtotal = 0.0;
            if ($quote_discount_amount) {
                foreach ($items as $item) {
                    if ( ! empty($item->item_name)) {
                        $items_subtotal += standardize_amount($item->item_quantity) * standardize_amount($item->item_price);
                    }
                }
            }

            // New discounts (for legacy_calculation false) - since v1.6.3 Need if taxes applied after discounts
            $global_discount = [
                'amount'         => $quote_discount_amount ? standardize_amount($quote_discount_amount) : 0.0,
                'percent'        => $quote_discount_percent ? standardize_amount($quote_discount_percent) : 0.0,
                'item'           => 0.0, // Updated by ref (Need for quote_item_subtotal calculation in Mdl_quote_amounts)
                'items_subtotal' => $items_subtotal,
            ];

            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                // Shift to false (by default). Need true? See Dev Note on ipconfig example
                $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
            }

            foreach ($items as $item) {
                // Check if an item has either a quantity + price or name or description
                if ( ! empty($item->item_name)) {
                    // Standardize item data
                    $item->item_quantity        = $item->item_quantity ? standardize_amount($item->item_quantity) : 0.0;
                    $item->item_price           = $item->item_price ? standardize_amount($item->item_price) : 0.0;
                    $item->item_discount_amount = $item->item_discount_amount ? standardize_amount($item->item_discount_amount) : null;
                    $item->item_product_id      = $item->item_product_id ? $item->item_product_id : null;
                    $item->item_product_unit_id = $item->item_product_unit_id ? $item->item_product_unit_id : null;
                    $item->item_product_unit    = $this->unit->get_name($item->item_product_unit_id, $item->item_quantity);

                    $item_id = ($item->item_id) ?: null;
                    unset($item->item_id);

                    $this->quoteitems->save($item_id, $item, $global_discount);
                } elseif (empty($item->item_name) && ( ! empty($item->item_quantity) || ! empty($item->item_price))) {
                    // Throw an error message and use the form validation for that (todo: where the translations of: The .* field is required.)
                    $this->load->library('form_validation');
                    $this->form_validation->set_rules('item_name', trans('item'), 'required');
                    $this->form_validation->run();

                    $response = [
                        'success'           => 0,
                        'validation_errors' => [
                            'item_name' => form_error('item_name', '', ''),
                        ],
                    ];

                    exit(json_encode($response));
                }
            }

            $quote_status_id = $this->input->post('quote_status_id');

            // Generate new quote number if needed
            $quote_number = $this->input->post('quote_number');

            if (empty($quote_number) && $quote_status_id != 1) {
                $quote_group_id = $this->quote->get_invoice_group_id($quote_id);
                $quote_number   = $this->quote->get_quote_number($quote_group_id);
            }

            // Sometime global discount total value (round) need little adjust to be valid in ZugFerd2.3 standard
            if ( ! config_item('legacy_calculation') && $quote_discount_amount && $quote_discount_amount != $global_discount['item']) {
                // Adjust amount to reflect real calculation (cents)
                $quote_discount_amount = $global_discount['item'];
            }

            $db_array = [
                'quote_number'           => $quote_number,
                'quote_status_id'        => $quote_status_id,
                'quote_date_created'     => date_to_mysql($this->input->post('quote_date_created')),
                'quote_date_expires'     => date_to_mysql($this->input->post('quote_date_expires')),
                'quote_password'         => $this->input->post('quote_password'),
                'notes'                  => $this->input->post('notes'),
                'quote_discount_amount'  => standardize_amount($quote_discount_amount),
                'quote_discount_percent' => standardize_amount($quote_discount_percent),
            ];

            $this->quote->save($quote_id, $db_array, $global_discount);

            if (config_item('legacy_calculation')) {
                // Recalculate for discounts
                $this->load->model('quotes/quoteamount');
                $this->quoteamounts->calculate($quote_id, $global_discount);
            }

            $response = [
                'success' => 1,
            ];
        } else {
            log_message('error', '980: I wasnt able to run the validation validation_rules_save_quote');

            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        // Save all custom fields
        if ($this->input->post('custom')) {
            $db_array = [];

            $values = [];
            foreach ($this->input->post('custom') as $custom) {
                if (preg_match("/^(.*)\[\]$/i", $custom['name'], $matches)) {
                    $values[$matches[1]][] = $custom['value'];
                } else {
                    $values[$custom['name']] = $custom['value'];
                }
            }

            foreach ($values as $key => $value) {
                preg_match("/^custom\[(.*?)\](?:\[\]|)$/", $key, $matches);
                if ($matches) {
                    $db_array[$matches[1]] = $value;
                }
            }

            $this->load->model('custom_fields/quotecustom');
            $result = $this->quotecustom->save_custom($quote_id, $db_array);
            if ($result !== true) {
                $response = [
                    'success'           => 0,
                    'validation_errors' => $result,
                ];

                exit(json_encode($response));
            }
        }

        exit(json_encode($response));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function save_quote_tax_rate()
     */
    public function save_quote_tax_rate()
    {
        $this->load->model('quotes/quotetaxrate');

        if ($this->quotetaxrates->run_validation()) {
            // Only Legacy calculation have global taxes - since v1.6.3
            config_item('legacy_calculation') && $this->quotetaxrates->save();

            $response = [
                'success' => 1,
            ];
        } else {
            $response = [
                'success'           => 0,
                'validation_errors' => $this->quotetaxrates->validation_errors,
            ];
        }

        exit(json_encode($response));
    }

    /**
     * @param $quote_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function delete_item()
     */
    public function delete_item($quote_id)
    {
        $success = 0;
        $item_id = $this->input->post('item_id');
        $this->load->model('quotes/quote');

        // Only continue if the quote exists or no item id was provided
        if ($this->quote->get_by_id($quote_id) || empty($item_id)) {
            // Delete quote item
            $this->load->model('quotes/quoteitem');
            $item = $this->quoteitems->delete($item_id);

            // Check if deletion was successful
            if ($item) {
                $success = 1;
            }
        }

        // Return the response
        exit(json_encode(['success' => $success]));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function get_item()
     */
    public function get_item()
    {
        $this->load->model('quotes/quoteitem');

        $item = $this->quoteitems->get_by_id($this->input->post('item_id'));

        exit(json_encode($item));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function modal_copy_quote()
     */
    public function modal_copy_quote()
    {
        $this->load->module('layout');
        $this->load->model([
            'quotes/mdl_quotes',
            'invoice_groups/mdl_invoice_group',
            'tax_rates/mdl_tax_rate',
            'clients/mdl_clients',
        ]);

        $data = [
            'invoice_groups' => $this->invoicegroups->get()->result(),
            'tax_rates'      => $this->taxrates->get()->result(),
            'quote_id'       => $this->security->xss_clean($this->input->post('quote_id')),
            'quote'          => $this->quote->where('ip_quotes.quote_id', $this->input->post('quote_id'))->get()->row(),
            'client'         => $this->client->get_by_id($this->input->post('client_id')),
        ];

        $this->layout->load_view('quotes/modal_copy_quote', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function copy_quote()
     */
    public function copy_quote()
    {
        $this->load->model([
            'quotes/mdl_quotes',
            'quotes/mdl_quote_item',
            'quotes/mdl_quote_tax_rate',
        ]);

        if ($this->quote->run_validation()) {
            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                // Shift to false (by default). Need true? See Dev Note on ipconfig example
                $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
            }

            $target_id = $this->quote->save();
            $source_id = $this->input->post('quote_id');

            $this->quote->copy_quote($source_id, $target_id);

            $response = [
                'success'  => 1,
                'quote_id' => $target_id,
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        exit(json_encode($response));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function modal_change_user()
     */
    public function modal_change_user()
    {
        $this->load->module('layout');
        $this->load->model('users/user');

        $data = [
            'user_id'  => $this->security->xss_clean($this->input->post('user_id')),
            'quote_id' => $this->security->xss_clean($this->input->post('quote_id')),
            'users'    => $this->user->get_latest(),
        ];

        $this->layout->load_view('layout/ajax/modal_change_user_client', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function change_user()
     */
    public function change_user()
    {
        $this->load->model([
            'quotes/mdl_quotes',
            'users/mdl_users',
        ]);

        // Get the user ID
        $user_id = $this->security->xss_clean($this->input->post('user_id'));
        $user    = $this->user->where('ip_users.user_id', $user_id)->get()->row();

        if ( ! empty($user)) {
            $quote_id = $this->input->post('quote_id');

            $db_array = [
                'user_id' => $user_id,
            ];
            $this->db->where('quote_id', $quote_id);
            $this->db->update('ip_quotes', $db_array);

            $response = [
                'success'  => 1,
                'quote_id' => $this->security->xss_clean($quote_id),
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        exit(json_encode($response));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function modal_change_client()
     */
    public function modal_change_client()
    {
        $this->load->module('layout');
        $this->load->model('clients/client');

        $data = [
            'client_id' => $this->security->xss_clean($this->input->post('client_id')),
            'quote_id'  => $this->security->xss_clean($this->input->post('quote_id')),
            'clients'   => $this->client->get_latest(),
        ];

        $this->layout->load_view('layout/ajax/modal_change_user_client', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function change_client()
     */
    public function change_client()
    {
        $this->load->model([
            'quotes/mdl_quotes',
            'clients/mdl_clients',
        ]);

        // Get the client ID
        $client_id = $this->security->xss_clean($this->input->post('client_id'));
        $client    = $this->client->where('ip_clients.client_id', $client_id)->get()->row();

        if ( ! empty($client)) {
            $quote_id = $this->input->post('quote_id');

            $db_array = [
                'client_id' => $client_id,
            ];
            $this->db->where('quote_id', $quote_id);
            $this->db->update('ip_quotes', $db_array);

            $response = [
                'success'  => 1,
                'quote_id' => $this->security->xss_clean($quote_id),
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        exit(json_encode($response));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function modal_create_quote()
     */
    public function modal_create_quote()
    {
        $this->load->module('layout');
        $this->load->model([
            'invoice_groups/mdl_invoice_group',
            'tax_rates/mdl_tax_rate',
            'clients/mdl_clients',
        ]);

        $data = [
            'invoice_groups' => $this->invoicegroups->get()->result(),
            'tax_rates'      => $this->taxrates->get()->result(),
            'client'         => $this->client->get_by_id($this->input->post('client_id')),
            'clients'        => $this->client->get_latest(),
        ];

        $this->layout->load_view('quotes/modal_create_quote', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function create()
     */
    public function create()
    {
        $this->load->model('quotes/quote');

        if ($this->quote->run_validation()) {
            $quote_id = $this->quote->create();

            $response = [
                'success'  => 1,
                'quote_id' => $quote_id,
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        exit(json_encode($response));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function modal_quote_to_invoice()
     */
    public function modal_quote_to_invoice($quote_id)
    {
        $this->load->model([
            'invoice_groups/mdl_invoice_group',
            'quotes/mdl_quotes',
        ]);

        $data = [
            'invoice_groups' => $this->invoicegroups->get()->result(),
            'quote_id'       => $this->security->xss_clean($quote_id),
            'quote'          => $this->quote->where('ip_quotes.quote_id', $quote_id)->get()->row(),
        ];

        $this->load->view('quotes/modal_quote_to_invoice', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/quotes/controllers/Ajax.php
     * @legacy-function quote_to_invoice()
     */
    public function quote_to_invoice()
    {
        $this->load->model([
            'invoices/mdl_invoices',
            'invoices/mdl_item',
            'invoices/mdl_invoice_tax_rate',
            'quotes/mdl_quotes',
            'quotes/mdl_quote_item',
            'quotes/mdl_quote_tax_rate',
        ]);

        if ($this->invoice->run_validation()) {
            // Get the quote
            $quote_id = $this->input->post('quote_id');
            $quote    = $this->quote->get_by_id($quote_id);

            // Create new invoice
            $invoice_id = $this->invoice->create(null, false);

            // Update the discounts
            $this->db->where('invoice_id', $invoice_id);
            $this->db->set('invoice_discount_amount', $quote->quote_discount_amount);
            $this->db->set('invoice_discount_percent', $quote->quote_discount_percent);
            $this->db->update('ip_invoices');

            // Save the invoice id to the quote
            $this->db->where('quote_id', $quote_id);
            $this->db->set('invoice_id', $invoice_id);
            $this->db->update('ip_quotes');

            // Discounts calculation - since v1.6.3 Need if taxes applied after discounts
            $global_discount = [
                'amount'         => $quote->quote_discount_amount,
                'percent'        => $quote->quote_discount_percent,
                'item'           => 0.0, // Updated by ref (Need for quote_item_subtotal calculation in Mdl_quote_amounts)
                'items_subtotal' => $this->quoteitems->get_items_subtotal($quote->quote_id),
            ];
            unset($quote); // Free memory

            $quote_items = $this->quoteitems->where('quote_id', $this->input->post('quote_id'))->get()->result();

            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                // Shift to false (by default). Need true? See Dev Note on ipconfig example
                $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
            }

            foreach ($quote_items as $quote_item) {
                $db_array = [
                    'invoice_id'           => $invoice_id,
                    'item_tax_rate_id'     => $quote_item->item_tax_rate_id,
                    'item_product_id'      => $quote_item->item_product_id,
                    'item_name'            => $quote_item->item_name,
                    'item_description'     => $quote_item->item_description,
                    'item_quantity'        => $quote_item->item_quantity,
                    'item_price'           => $quote_item->item_price,
                    'item_product_unit_id' => $quote_item->item_product_unit_id,
                    'item_product_unit'    => $quote_item->item_product_unit,
                    'item_discount_amount' => $quote_item->item_discount_amount,
                    'item_order'           => $quote_item->item_order,
                ];

                $this->item->save(null, $db_array, $global_discount);
            }

            $quote_tax_rates = $this->quotetaxrates->where('quote_id', $this->input->post('quote_id'))->get()->result();

            foreach ($quote_tax_rates as $quote_tax_rate) {
                $db_array = [
                    'invoice_id'              => $invoice_id,
                    'tax_rate_id'             => $quote_tax_rate->tax_rate_id,
                    'include_item_tax'        => $quote_tax_rate->include_item_tax,
                    'invoice_tax_rate_amount' => $quote_tax_rate->quote_tax_rate_amount,
                ];

                $this->invoicetaxrates->save(null, $db_array);
            }

            $response = [
                'success'    => 1,
                'invoice_id' => $invoice_id,
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'validation_errors' => json_errors(),
            ];
        }

        exit(json_encode($response));
    }
}
