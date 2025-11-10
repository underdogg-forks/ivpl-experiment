<?php

namespace App\Modules\Products\Controllers;

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
class ProductsController extends AdminController
{
    /**
     * Products constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('products/product');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->product->paginate(site_url('products/index'), $page);
        $products = $this->product->result();

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

        if ($this->product->run_validation()) {
            // Get the db array
            $db_array = $this->product->db_array();
            $this->product->save($id, $db_array);
            redirect('products');
        }

        if ($id && ! $this->input->post('btn_submit') && ! $this->product->prep_form($id)) {
            show_404();
        }

        $this->load->model('families/family');
        $this->load->model('units/unit');
        $this->load->model('tax_rates/taxrates');

        $this->layout->set(
            [
                'families'  => $this->family->get()->result(),
                'units'     => $this->unit->get()->result(),
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
        $this->product->delete($id);
        redirect('products');
    }
}
