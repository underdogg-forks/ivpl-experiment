<?php

namespace App\Modules\Invoices\Controllers;

use App\Core\AdminController;
use App\Libraries\DocumentItemProcessor;
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
class InvoicesAjaxController extends AdminController
{
    public $ajax_controller = true;

    /**
     * Document item processor (Dependency Inversion Principle)
     */
    private DocumentItemProcessor $itemProcessor;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        // Dependency Injection - SOLID principles
        $this->itemProcessor = new DocumentItemProcessor();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function save()
     */
    public function save()
    {
        $this->load->model([
            'invoices/mdl_item',
            'invoices/mdl_invoices',
            'units/mdl_units',
            'invoices/mdl_invoice_sumex',
        ]);

        $invoice_id = $this->security->xss_clean($this->input->post('invoice_id', true));

        $this->invoice->set_id($invoice_id);

        // Early return if validation fails (Early Return principle)
        if (!$this->invoice->run_validation('validation_rules_save_invoice')) {
            return;
        }

        $items = json_decode($this->input->post('items'));

        $invoice_discount_percent = (float) $this->input->post('invoice_discount_percent');
        $invoice_discount_amount  = (float) $this->input->post('invoice_discount_amount');

        // Use DocumentItemProcessor to normalize discounts (DRY principle)
        $normalizedDiscounts = $this->itemProcessor->normalizeDiscounts(
            $invoice_discount_percent,
            $invoice_discount_amount
        );

        $invoice_discount_percent = $normalizedDiscounts['percent'];
        $invoice_discount_amount = $normalizedDiscounts['amount'];

        // Calculate items subtotal using DocumentItemProcessor (Dynamic Programming)
        $items_subtotal = 0.0;
        if ($invoice_discount_amount) {
            $items_subtotal = $this->itemProcessor->calculateItemsSubtotal($items);
        }

        // Build global discount using DocumentItemProcessor (DRY principle)
        $global_discount = $this->itemProcessor->buildGlobalDiscount(
            $invoice_discount_percent,
            $invoice_discount_amount,
            $items_subtotal
        );

        // Automatic calculation mode
        if (SettingsCache::isEnabled('einvoicing')) {
            // Shift to false (by default). Need true? See Dev Note on ipconfig example
            $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
        }

        foreach ($items as $item) {
            // Early continue if no item name (Early Return principle)
            if (empty($item->item_name)) {
                // Check if quantity or price exists - throw error
                if (!empty($item->item_quantity) || !empty($item->item_price)) {
                    $this->returnValidationError('item_name', trans('item'));
                    return;
                }
                continue;
            }

            // Process item data using DocumentItemProcessor (DRY + Dynamic Programming)
            $item = $this->itemProcessor->processItemData($item);

            // Handle item_date if exists
            if (property_exists($item, 'item_date')) {
                $item->item_date = $item->item_date ? date_to_mysql($item->item_date) : null;
            }

            $item_id = ($item->item_id) ?: null;
            unset($item->item_id);

            // Handle task association
            if ( ! $item->item_task_id) {
                unset($item->item_task_id);
            } else {
                if (empty($this->task)) {
                    $this->load->model('tasks/task');
                }

                $this->task->update_status(4, $item->item_task_id);
            }

            $this->item->save($item_id, $item, $global_discount);
        }

        $invoice_status_id = $this->input->post('invoice_status_id');

        // Generate new invoice number if needed
        $invoice_number = $this->input->post('invoice_number');

        if (empty($invoice_number) && $invoice_status_id != 1) {
            $invoice_group_id = $this->invoice->get_invoice_group_id($invoice_id);
            $invoice_number   = $this->invoice->get_invoice_number($invoice_group_id);
        }

        // Sometime global discount total value (round) need little adjust to be valid in ZugFerd2.3 standard
        if ( ! config_item('legacy_calculation') && $invoice_discount_amount && $invoice_discount_amount != $global_discount['item']) {
            // Adjust amount to reflect real calculation (cents)
            $invoice_discount_amount = $global_discount['item'];
        }

        $db_array = [
            'invoice_number'           => $invoice_number,
            'invoice_status_id'        => $invoice_status_id,
            'invoice_date_created'     => date_to_mysql($this->input->post('invoice_date_created')),
            'invoice_date_due'         => date_to_mysql($this->input->post('invoice_date_due')),
            'invoice_password'         => $this->security->xss_clean($this->input->post('invoice_password')),
            'invoice_terms'            => $this->security->xss_clean($this->input->post('invoice_terms')),
            'payment_method'           => $this->security->xss_clean($this->input->post('payment_method')),
            'invoice_discount_amount'  => standardize_amount($invoice_discount_amount),
            'invoice_discount_percent' => standardize_amount($invoice_discount_percent),
        ];

        // check if status changed to sent, the feature is enabled and settings is set to sent
        if ($this->config->item('disable_read_only') === false && $invoice_status_id == get_setting('read_only_toggle')) {
            $db_array['is_read_only'] = 1;
        }

        $this->invoice->save($invoice_id, $db_array);

        $sumexInvoice = $this->invoice->where('sumex_invoice', $invoice_id)->get()->num_rows();

        if ($sumexInvoice >= 1) {
            $sumex_array = [
                'sumex_invoice'        => $invoice_id,
                'sumex_reason'         => $this->input->post('invoice_sumex_reason'),
                'sumex_diagnosis'      => $this->input->post('invoice_sumex_diagnosis'),
                'sumex_treatmentstart' => date_to_mysql($this->input->post('invoice_sumex_treatmentstart')),
                'sumex_treatmentend'   => date_to_mysql($this->input->post('invoice_sumex_treatmentend')),
                'sumex_casedate'       => date_to_mysql($this->input->post('invoice_sumex_casedate')),
                'sumex_casenumber'     => $this->input->post('invoice_sumex_casenumber'),
                'sumex_observations'   => $this->input->post('invoice_sumex_observations'),
            ];

            $this->invoicesumex->save($invoice_id, $sumex_array);
        }

        if (config_item('legacy_calculation')) {
            // Recalculate for discounts
            $this->load->model('invoices/invoiceamount');
            $this->invoiceamounts->calculate($invoice_id, $global_discount);
        }

        $response = [
            'success' => 1,
        ];

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

            $this->load->model('custom_fields/invoicecustom');
            $result = $this->invoicecustom->save_custom($invoice_id, $db_array);
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
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function save_invoice_tax_rate()
     */
    public function save_invoice_tax_rate()
    {
        $this->load->model('invoices/invoicetaxrate');

        if ($this->invoicetaxrates->run_validation()) {
            // Only Legacy calculation have global taxes - since v1.6.3
            config_item('legacy_calculation') && $this->invoicetaxrates->save();

            $response = [
                'success' => 1,
            ];
        } else {
            $response = [
                'success'           => 0,
                'validation_errors' => $this->invoicetaxrates->validation_errors,
            ];
        }

        exit(json_encode($response));
    }

    /**
     * @param $invoice_id
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function delete_item()
     */
    public function delete_item($invoice_id)
    {
        $success = 0;
        $item_id = $this->security->xss_clean($this->input->post('item_id'));
        $this->load->model('invoices/invoice');

        // Only continue if the invoice exists or no item id was provided
        if ($this->invoice->get_by_id($invoice_id) || empty($item_id)) {
            // Delete invoice item
            $this->load->model('invoices/item');
            $item = $this->item->delete($item_id);

            // Check if deletion was successful
            if ($item) {
                $success = 1;
                // Mark task as complete from invoiced
                if (isset($item->item_task_id) && $item->item_task_id) {
                    $this->load->model('tasks/task');
                    $this->task->update_status(3, $item->item_task_id);
                }
            }
        }

        // Return the response
        exit(json_encode(['success' => $success]));
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function get_item()
     */
    public function get_item()
    {
        $this->load->model('invoices/item');

        $item = $this->item->get_by_id($this->security->xss_clean($this->input->post('item_id', true)));

        echo json_encode($item);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_copy_invoice()
     */
    public function modal_copy_invoice()
    {
        $this->load->module('layout');

        $this->load->model([
            'invoices/mdl_invoices',
            'invoice_groups/mdl_invoice_group',
            'tax_rates/mdl_tax_rate',
            'clients/mdl_clients',
        ]);

        $data = [
            'invoice_groups' => $this->invoicegroups->get()->result(),
            'tax_rates'      => $this->taxrates->get()->result(),
            'invoice_id'     => $this->security->xss_clean($this->input->post('invoice_id')),
            'invoice'        => $this->invoice->where('ip_invoices.invoice_id', $this->security->xss_clean($this->input->post('invoice_id')))->get()->row(),
            'client'         => $this->client->get_by_id($this->input->post('client_id')),
        ];

        $this->layout->load_view('invoices/modal_copy_invoice', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function copy_invoice()
     */
    public function copy_invoice()
    {
        $this->load->model([
            'invoices/mdl_invoices',
            'invoices/mdl_item',
            'invoices/mdl_invoice_tax_rate',
        ]);

        if ($this->invoice->run_validation()) {
            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                // Shift to false (by default). Need true? See Dev Note on ipconfig example
                $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
            }

            $target_id = $this->invoice->save();
            $source_id = $this->security->xss_clean($this->input->post('invoice_id'));

            $this->invoice->copy_invoice($source_id, $target_id);

            $response = [
                'success'    => 1,
                'invoice_id' => $target_id,
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
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_change_user()
     */
    public function modal_change_user()
    {
        $this->load->module('layout');
        $this->load->model('users/user');

        $data = [
            'user_id'    => $this->security->xss_clean($this->input->post('user_id')),
            'invoice_id' => $this->security->xss_clean($this->input->post('invoice_id')),
            'users'      => $this->user->get_latest(),
        ];

        $this->layout->load_view('layout/ajax/modal_change_user_client', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function change_user()
     */
    public function change_user()
    {
        $this->load->model([
            'invoices/mdl_invoices',
            'users/mdl_users',
        ]);

        // Get the user ID
        $user_id = $this->security->xss_clean($this->input->post('user_id'));
        $user    = $this->user->where('ip_users.user_id', $user_id)->get()->row();

        if ( ! empty($user)) {
            $invoice_id = $this->security->xss_clean($this->input->post('invoice_id'));

            $db_array = [
                'user_id' => $user_id,
            ];
            $this->db->where('invoice_id', $invoice_id);
            $this->db->update('ip_invoices', $db_array);

            $response = [
                'success'    => 1,
                'invoice_id' => $this->security->xss_clean($invoice_id),
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
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_change_client()
     */
    public function modal_change_client()
    {
        $this->load->module('layout');
        $this->load->model('clients/client');

        $data = [
            'client_id'  => $this->security->xss_clean($this->input->post('client_id')),
            'invoice_id' => $this->security->xss_clean($this->input->post('invoice_id')),
            'clients'    => $this->client->get_latest(),
        ];

        $this->layout->load_view('layout/ajax/modal_change_user_client', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function change_client()
     */
    public function change_client()
    {
        $this->load->model([
            'invoices/mdl_invoices',
            'clients/mdl_clients',
        ]);

        // Get the client ID
        $client_id = $this->security->xss_clean($this->input->post('client_id'));
        $client    = $this->client->where('ip_clients.client_id', $client_id)->get()->row();

        if ( ! empty($client)) {
            $invoice_id = $this->security->xss_clean($this->input->post('invoice_id'));

            $db_array = [
                'client_id' => $client_id,
            ];
            $this->db->where('invoice_id', $invoice_id);
            $this->db->update('ip_invoices', $db_array);

            $response = [
                'success'    => 1,
                'invoice_id' => $this->security->xss_clean($invoice_id),
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
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_create_invoice()
     */
    public function modal_create_invoice()
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

        $this->layout->load_view('invoices/modal_create_invoice', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function create()
     */
    public function create()
    {
        $this->load->model('invoices/invoice');

        if ($this->invoice->run_validation()) {
            $invoice_id = $this->invoice->create();

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

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function create_recurring()
     */
    public function create_recurring()
    {
        $this->load->model('invoices/invoicerecurring');

        if ($this->invoice_recurring->run_validation()) {
            $this->invoice_recurring->save();

            $response = [
                'success' => 1,
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
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_create_recurring()
     */
    public function modal_create_recurring()
    {
        $this->load->module('layout');

        $this->load->model('invoices/invoicerecurring');

        $data = [
            'invoice_id'        => $this->security->xss_clean($this->input->post('invoice_id')),
            'recur_frequencies' => $this->invoice_recurring->recur_frequencies,
        ];

        $this->layout->load_view('invoices/modal_create_recurring', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function get_recur_start_date()
     */
    public function get_recur_start_date()
    {
        $invoice_date    = $this->input->post('invoice_date');
        $recur_frequency = $this->input->post('recur_frequency');

        echo increment_user_date($invoice_date, $recur_frequency);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function modal_create_credit()
     */
    public function modal_create_credit()
    {
        $this->load->module('layout');
        $this->load->model([
            'invoices/mdl_invoices',
            'invoice_groups/mdl_invoice_group',
            'tax_rates/mdl_tax_rate',
        ]);

        $data = [
            'invoice_groups' => $this->invoicegroups->get()->result(),
            'tax_rates'      => $this->taxrates->get()->result(),
            'invoice_id'     => $this->security->xss_clean($this->input->post('invoice_id')),
            'invoice'        => $this->invoice->where('ip_invoices.invoice_id', $this->security->xss_clean($this->input->post('invoice_id')))->get()->row(),
        ];

        $this->layout->load_view('invoices/modal_create_credit', $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/invoices/controllers/Ajax.php
     * @legacy-function create_credit()
     */
    public function create_credit()
    {
        $this->load->model([
            'invoices/mdl_invoices',
            'invoices/mdl_item',
            'invoices/mdl_invoice_tax_rate',
        ]);

        if ($this->invoice->run_validation()) {
            // Automatic calculation mode
            if (get_setting('einvoicing')) {
                // Shift to false (by default). Need true? See Dev Note on ipconfig example
                $this->config->set_item('legacy_calculation', ! empty($this->input->post('legacy_calculation')));
            }

            $target_id = $this->invoice->save();
            $source_id = $this->security->xss_clean($this->input->post('invoice_id'));

            $this->invoice->copy_credit_invoice($source_id, $target_id);

            // Set source invoice to read-only
            if ($this->config->item('disable_read_only') == false) {
                $this->invoice->where('invoice_id', $source_id);
                $this->invoice->update('ip_invoices', ['is_read_only' => '1']);
            }

            // Set target invoice to credit invoice
            $this->invoice->where('invoice_id', $target_id);
            $this->invoice->update('ip_invoices', ['creditinvoice_parent_id' => $source_id]);

            $this->invoice->where('invoice_id', $target_id);
            $this->invoice->update('ip_invoice_amounts', ['invoice_sign' => '-1']);

            $response = [
                'success'    => 1,
                'invoice_id' => $target_id,
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
     * Return validation error response and exit
     * DRY: Extracted common error handling pattern
     *
     * @param string $fieldName The field name
     * @param string $fieldLabel The field label for error message
     * @return void
     */
    private function returnValidationError(string $fieldName, string $fieldLabel): void
    {
        $this->load->library('form_validation');
        $this->form_validation->set_rules($fieldName, $fieldLabel, 'required');
        $this->form_validation->run();

        $response = [
            'success'           => 0,
            'validation_errors' => [
                $fieldName => form_error($fieldName, '', ''),
            ],
        ];

        exit(json_encode($response));
    }
}
