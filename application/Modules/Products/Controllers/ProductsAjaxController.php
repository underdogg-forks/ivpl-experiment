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
class ProductsAjaxController extends AdminController
{
    public $ajax_controller = true;

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/controllers/Ajax.php
     * @legacy-function modal_product_lookups()
     */
    public function modal_product_lookups()
    {
        $filter_product = $this->input->get('filter_product', true);
        $filter_family  = $this->input->get('filter_family', true);
        $reset_table    = $this->input->get('reset_table', true);

        $this->load->model('products/product');
        $this->load->model('families/family');

        if ( ! empty($filter_family)) {
            $this->product->by_family($filter_family);
            $filter_family = $this->security->xss_clean($filter_family);
        }

        if ( ! empty($filter_product)) {
            $this->product->by_product($filter_product);
            $filter_product = $this->security->xss_clean($filter_product);
        }

        $products = $this->product->get()->result();
        $families = $this->family->get()->result();

        $default_item_tax_rate = get_setting('default_item_tax_rate');
        $default_item_tax_rate = $default_item_tax_rate !== '' ?: 0;

        $data = [
            'products'              => $products,
            'families'              => $families,
            'filter_product'        => $filter_product,
            'filter_family'         => $filter_family,
            'default_item_tax_rate' => $default_item_tax_rate,
        ];

        // Determine which view to render based on filters
        $viewPath = ($filter_product || $filter_family || $reset_table) 
            ? 'products/partial_product_table_modal' 
            : 'products/modal_product_lookups';
        
        $this->renderViewAsJson($viewPath, $data);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/controllers/Ajax.php
     * @legacy-function process_product_selections()
     */
    public function process_product_selections()
    {
        $this->load->model('products/product');

        $products = $this->product->where_in('product_id', $this->input->post('product_ids'))->get()->result();

        foreach ($products as $product) {
            $product->product_price = format_amount($product->product_price);
        }

        echo json_encode($products);
    }
}
