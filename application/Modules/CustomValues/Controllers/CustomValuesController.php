<?php

namespace App\Modules\CustomValues\Controllers;

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
class Custom_Values extends AdminController
{
    /**
     * Custom_Values constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('custom_values/customvalues');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->customvalues->grouped()->paginate(site_url('custom_values/index'), $page);
        $custom_values = $this->customvalues->result();

        $this->load->model('custom_fields/customfields');
        // Determine which name of table custom field to load
        $custom_tables = $this->customfields->custom_tables();
        // load positions by table name
        $positions = $this->customfields->get_positions(true);

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_custom_values'),
                'filter_method'      => 'filter_custom_values',
                'custom_tables'      => $custom_tables,
                'custom_values'      => $custom_values,
                'positions'          => $positions,
            ]
        );
        $this->layout->buffer('content', 'custom_values/index');
        $this->layout->render();
    }

    public function field($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('custom_values');
        }

        $this->load->model('custom_fields/customfields');
        $field  = $this->customfields->get_by_id($id);
        $result = $this->customvalues->get_by_fid($id)->result();
        // Determine which name of table custom field to load
        $custom_tables = $this->customfields->custom_tables();

        $positions = $this->customfields->get_positions(true);
        $position  = $positions[$field->custom_field_table][$field->custom_field_location];
        unset($positions);

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_custom_values'),
                'filter_method'      => 'filter_custom_values_field',
                'id'                 => $id,
                'field'              => $field,
                'elements'           => $result,
                'custom_field_usage' => $this->customfields->used($id),
                'position'           => $position,
                'table'              => $custom_tables[$field->custom_field_table],
            ]
        );
        $this->layout->buffer('content', 'custom_values/field');
        $this->layout->render();
    }

    public function edit($id = null)
    {
        $value = $this->customvalues->get_by_id($id)->row();
        $fid   = $value->custom_field_id;

        if ($this->input->post('btn_cancel')) {
            redirect('custom_values/field/' . $fid);
        }

        if ($this->customvalues->run_validation()) {
            $this->customvalues->save($id);
            redirect('custom_values/field/' . $fid);
        }

        $this->load->model('custom_fields/customfields');
        $positions = $this->customfields->get_positions(true);
        $position  = $positions[$value->custom_field_table][$value->custom_field_location];
        unset($positions);

        $this->layout->set(
            [
                'id'                 => $id,
                'fid'                => $fid,
                'value'              => $value,
                'position'           => $position,
                'custom_field_usage' => $this->customvalues->used($id),
            ]
        );
        $this->layout->buffer('content', 'custom_values/edit');
        $this->layout->render();
    }

    public function create($id = null)
    {
        if ( ! $id) {
            redirect('custom_values');
        }

        $fid = $id;

        if ($this->input->post('btn_cancel')) {
            redirect('custom_values/field/' . $fid);
        }

        if ($this->customvalues->run_validation()) {
            $this->customvalues->save_custom($fid);
            redirect('custom_values/field/' . $fid);
        }

        $this->load->model('custom_fields/customfields');
        $field = $this->customfields->get_by_id($id);

        // Determine which name of table custom field to load
        $custom_tables = $this->customfields->custom_tables();
        $table         = $custom_tables[$field->custom_field_table];
        unset($custom_tables);

        $positions = $this->customfields->get_positions(true);
        $position  = $positions[$field->custom_field_table][$field->custom_field_location];
        unset($positions);

        $this->layout->set(
            [
                'id'       => $id,
                'field'    => $field,
                'table'    => $table,
                'position' => $position,
            ]
        );
        $this->layout->buffer('content', 'custom_values/new');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        if ( ! $this->customvalues->delete($id)) {
            $this->session->set_flashdata('alert_info', trans('id') . sprintf(' "%s" ', $id) . trans('custom_values_used_not_deletable'));
        }

        $fid = $this->input->post('custom_field_id');
        redirect('custom_values' . ($fid ? '/field/' . $fid : ''));
    }
}
