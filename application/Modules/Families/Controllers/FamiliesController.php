<?php

namespace App\Modules\Families\Controllers;

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
class FamiliesController extends \Admin_Controller
{
    /**
     * Families constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('families/families');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->families->paginate(site_url('families/index'), $page);
        $families = $this->families->result();

        $this->layout->set([
            'filter_display'     => true,
            'filter_placeholder' => trans('filter_families'),
            'filter_method'      => 'filter_families',
            'families'           => $families,
        ]);
        $this->layout->buffer('content', 'families/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('families');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->input->post('is_update') == 0 && $this->input->post('family_name') != '') {
            $check = $this->db->get_where('ip_families', ['family_name' => $this->input->post('family_name')])->result();

            if ( ! empty($check)) {
                $this->session->set_flashdata('alert_error', trans('family_already_exists'));
                redirect('families/form');
            }
        }

        if ($this->families->run_validation()) {
            $this->families->save($id);
            redirect('families');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->families->prep_form($id)) {
                show_404();
            }

            $this->families->set_form_value('is_update', true);
        }

        $this->layout->buffer('content', 'families/form');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->families->delete($id);
        redirect('families');
    }
}
