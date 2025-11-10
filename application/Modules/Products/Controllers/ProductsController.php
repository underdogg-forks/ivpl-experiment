<?php

namespace App\Modules\Products\Controllers;

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
class ProductsController extends \Admin_Controller
{
    /**
     * Products constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('products/products');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->products->paginate(site_url('products/index'), $page);
        $products = $this->products->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_products'),
                'filter_method'      => 'filter_products',
                'products'           => $products,
            ]
        );
        $this->layout->buffer('content', 'products/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('products');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->products->run_validation()) {
            // Get the db array
            $db_array = $this->products->db_array();
            $this->products->save($id, $db_array);
            redirect('products');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->products->prep_form($id)) {
            show_404();
        }

        $this->load->model('families/families');
        $this->load->model('units/units');
        $this->load->model('tax_rates/taxrates');

        $this->layout->set(
            [
                'families'  => $this->families->get()->result(),
                'units'     => $this->units->get()->result(),
                'tax_rates' => $this->taxrates->get()->result(),
            ]
        );

        $this->layout->buffer('content', 'products/form');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        $this->products->delete($id);
        redirect('products');
    }
}
