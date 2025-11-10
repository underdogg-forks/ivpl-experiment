<?php

namespace App\Modules\Tax_rates\Controllers;

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
class Tax_Rates extends \Admin_Controller
{
    /**
     * Tax_Rates constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('tax_rates/taxrates');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->taxrates->paginate(site_url('tax_rates/index'), $page);
        $tax_rates = $this->taxrates->result();

        $this->layout->set('tax_rates', $tax_rates);
        $this->layout->buffer('content', 'tax_rates/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('tax_rates');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->taxrates->run_validation()) {
            $this->taxrates->form_values['tax_rate_percent'] = standardize_amount($this->taxrates->form_values['tax_rate_percent']);

            // We need to use the correct decimal point for sql IPT-310
            $db_array                     = $this->taxrates->db_array();
            $db_array['tax_rate_percent'] = standardize_amount($this->input->post('tax_rate_percent'));

            $this->taxrates->save($id, $db_array);

            redirect('tax_rates');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->taxrates->prep_form($id)) {
            show_404();
        }

        $this->layout->buffer('content', 'tax_rates/form');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->taxrates->delete($id);
        redirect('tax_rates');
    }
}
