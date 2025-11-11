<?php

namespace App\Modules\Products\Models;

use App\Core\ResponseModel;

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
class Product extends ResponseModel
{
    public $table = 'ip_products';

    public $primary_key = 'ip_products.product_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS *', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_families.family_name, ip_products.product_name');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function default_join()
     */
    public function default_join()
    {
        $this->db->join('ip_families', 'ip_families.family_id = ip_products.family_id', 'left');
        $this->db->join('ip_units', 'ip_units.unit_id = ip_products.unit_id', 'left');
        $this->db->join('ip_tax_rates', 'ip_tax_rates.tax_rate_id = ip_products.tax_rate_id', 'left');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function by_product()
     */
    public function by_product($match)
    {
        $this->db->group_start();
        $this->db->like('ip_products.product_sku', $match);
        $this->db->or_like('ip_products.product_name', $match);
        $this->db->or_like('ip_products.product_description', $match);
        $this->db->group_end();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function by_family()
     */
    public function by_family($match)
    {
        $this->db->where('ip_products.family_id', $match);
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'product_sku' => [
                'field' => 'product_sku',
                'label' => trans('product_sku'),
            ],
            'product_name' => [
                'field' => 'product_name',
                'label' => trans('product_name'),
                'rules' => 'required',
            ],
            'product_description' => [
                'field' => 'product_description',
                'label' => trans('product_description'),
            ],
            'product_price' => [
                'field' => 'product_price',
                'label' => trans('product_price'),
                'rules' => 'required',
            ],
            'purchase_price' => [
                'field' => 'purchase_price',
                'label' => trans('purchase_price'),
            ],
            'provider_name' => [
                'field' => 'provider_name',
                'label' => trans('provider_name'),
            ],
            'family_id' => [
                'field' => 'family_id',
                'label' => trans('family'),
                'rules' => 'numeric',
            ],
            'unit_id' => [
                'field' => 'unit_id',
                'label' => trans('unit'),
                'rules' => 'numeric',
            ],
            'tax_rate_id' => [
                'field' => 'tax_rate_id',
                'label' => trans('tax_rate'),
                'rules' => 'numeric',
            ],
            // Sumex
            'product_tariff' => [
                'field' => 'product_tariff',
                'label' => trans('product_tariff'),
            ],
        ];
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/products/models/Mdl_product.php
     * @legacy-function db_array()
     */
    public function db_array()
    {
        $db_array = parent::db_array();

        $db_array['product_price']  = (empty($db_array['product_price']) ? null : standardize_amount($db_array['product_price']));
        $db_array['purchase_price'] = (empty($db_array['purchase_price']) ? null : standardize_amount($db_array['purchase_price']));
        $db_array['family_id']      = (empty($db_array['family_id']) ? null : $db_array['family_id']);
        $db_array['unit_id']        = (empty($db_array['unit_id']) ? null : $db_array['unit_id']);
        $db_array['tax_rate_id']    = (empty($db_array['tax_rate_id']) ? null : $db_array['tax_rate_id']);

        return $db_array;
    }
}
