<?php

namespace App\Modules\CustomFields\Controllers;

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
class Custom_Fields extends AdminController
{
    /**
     * Custom_Fields constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('custom_fields/customfields');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/controllers/CustomFields.php
     * @legacy-function index()
     */
    public function index(): void
    {
        // Display all custom_fields tables by default
        redirect('custom_fields/table/all');
    }

    /**
     * @param string $name of table (simple) NAME (more comprehensive) & why not a filter by type??? like I/Q payment & todo for product ;)
     * @param int    $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/controllers/CustomFields.php
     * @legacy-function table()
     */
    public function table(string $name = 'all', $page = 0): void
    {
        // Determine which name of table custom field to load
        $custom_tables = $this->customfields->custom_tables();
        if ($name != 'all' && in_array($name, $custom_tables)) {
            $this->customfields->by_table_name($name);
        }

        // Paginate before result
        $this->customfields->paginate(site_url('custom_fields/name/' . $name), $page);
        $custom_fields = $this->customfields->result();

        $this->load->model('custom_values/customvalues');
        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_custom_fields'),
                'filter_method'      => 'filter_custom_fields',

                'custom_fields'       => $custom_fields,
                'custom_tables'       => $custom_tables,
                'custom_value_fields' => $this->customvalues->custom_value_fields(),
                'positions'           => $this->customfields->get_positions(true),
            ]
        );
        $this->layout->buffer('content', 'custom_fields/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/controllers/CustomFields.php
     * @legacy-function form()
     */
    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('custom_fields');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->customfields->run_validation()) {
            $this->customfields->save($id);
            redirect('custom_fields');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->customfields->prep_form($id)) {
            show_404();
        }

        $this->layout->set(
            [
                'custom_field_id'       => $id,
                'custom_field_tables'   => $this->customfields->custom_tables(),
                'custom_field_types'    => $this->customfields->custom_types(),
                'custom_field_usage'    => $this->customfields->used($id),
                'custom_field_location' => $this->customfields->form_value('custom_field_location'),
                'positions'             => $this->customfields->get_positions(),
            ]
        );
        $this->layout->buffer('content', 'custom_fields/form');
        $this->layout->render();
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_fields/controllers/CustomFields.php
     * @legacy-function delete()
     */
    public function delete($id)
    {
        if ( ! $this->customfields->delete($id)) {
            $this->session->set_flashdata('alert_info', trans('id') . sprintf(' "%s" ', $id) . trans('custom_fields_used_not_deletable'));
        }

        // Return to page number of custom values or fields
        $r = empty($_SERVER['HTTP_REFERER']) ? 'custom_fields' : $_SERVER['HTTP_REFERER'];
        redirect($r);
    }
}
