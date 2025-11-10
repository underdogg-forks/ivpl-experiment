<?php

namespace App\Modules\Units\Controllers;

use App\Core\AdminController;

if ( ! defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/*
 * InvoicePlane
 *
 * @author		InvoicePlane Developers & Contributors
 * @copyright	Copyright (c) 2012 - 2018 InvoicePlane.com
 * @license		https://invoiceplane.com/license.txt
 * @link		https://invoiceplane.com
 */

#[AllowDynamicProperties]
class UnitsController extends AdminController
{
    /**
     * Units constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('units/unit');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->unit->paginate(site_url('units/index'), $page);
        $units = $this->unit->result();

        $this->layout->set('units', $units);
        $this->layout->buffer('content', 'units/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('units');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if (
            $this->input->post('is_update') == 0
            && $this->input->post('unit_name')      != ''
            && $this->input->post('unit_name_plrl') != ''
        ) {
            $check = $this->db->get_where('ip_units', ['unit_name' => $this->input->post('unit_name')])->result();

            if ( ! empty($check)) {
                $this->session->set_flashdata('alert_error', trans('unit_already_exists'));
                redirect('units/form');
            }
        }

        if ($this->unit->run_validation()) {
            $this->unit->save($id);
            redirect('units');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->unit->prep_form($id)) {
                show_404();
            }

            $this->unit->set_form_value('is_update', true);
        }

        $this->layout->buffer('content', 'units/form');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->unit->delete($id);
        redirect('units');
    }
}
