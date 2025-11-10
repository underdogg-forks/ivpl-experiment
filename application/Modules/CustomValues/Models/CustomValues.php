<?php

namespace App\Modules\CustomValues\Models;

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
class CustomValues extends \MY_Model
{
    public $table = 'ip_custom_values';

    public $primary_key = 'ip_custom_values.custom_values_id';

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function custom_types()
     */
    public static function custom_types()
    {
        return array_merge(self::user_input_types(), self::custom_value_fields());
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function user_input_types()
     */
    public static function user_input_types()
    {
        return [
            'TEXT',
            'DATE',
            'BOOLEAN',
        ];
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function custom_value_fields()
     */
    public static function custom_value_fields()
    {
        return [
            'SINGLE-CHOICE',
            'MULTIPLE-CHOICE',
        ];
    }

    /**
     * @param $fid
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function save_custom()
     */
    public function save_custom($fid)
    {
        $this->load->module('custom_fields');
        $field_custom = $this->mdl_custom_fields->get_by_id($fid);

        if ( ! $field_custom) {
            return;
        }

        $db_array                        = $this->db_array();
        $db_array['custom_values_field'] = $fid;

        parent::save(null, $db_array);
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'custom_values_value' => [
                'field' => 'custom_values_value',
                'label' => 'Value',
                'rules' => 'required',
            ],
        ];
    }

    /**
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function custom_tables()
     */
    public function custom_tables()
    {
        return [
            'ip_client_custom'  => 'client',
            'ip_invoice_custom' => 'invoice',
            'ip_payment_custom' => 'payment',
            'ip_quote_custom'   => 'quote',
            'ip_user_custom'    => 'user',
        ];
    }

    /**
     * @param int  $id
     * @param bool $get
     *
     * @return null|object
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function used()
     */
    public function used($id = null, $get = true)
    {
        if ( ! $id) {
            return;
        }

        $this->load->model('custom_fields/mdl_custom_fields');
        $cv = $this->get_by_id($id)->row();
        $cf = $this->mdl_custom_fields->get_by_id($cv->custom_values_field);
        unset($cv);
        $base = strtr($cf->custom_field_table, ['ip_' => '']) . '_fieldvalue';

        // Get values [SINGLE|MULTIPLE]-CHOICE
        $this->db->from($cf->custom_field_table);
        if ('SINGLE-CHOICE' == $cf->custom_field_type) {
            $this->db->where($base, $id);
        } else {
            $this->db->or_like($base, $id . ',')
                ->or_like($base, ',' . $id)
                ->or_where($base, $id);
        }

        return $get ? $this->db->get()->result() : $this->db;
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function delete()
     */
    public function delete($id): bool
    {
        if ( ! $this->used($id)) {
            parent::delete($id);

            return true;
        }

        return false;
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function delete_all_fid()
     */
    public function delete_all_fid($id)
    {
        $this->db->where('custom_values_field', $id)->delete($this->table);
    }

    /**
     * @param $id
     *
     * @return $this
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function get_by_fid()
     */
    public function get_by_fid($id)
    {
        return $this->where('custom_values_field', $id)->get();
    }

    /**
     * @param $id
     *
     * @return $this
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function get_by_column()
     */
    public function get_by_column($id)
    {
        return $this->where('custom_field_id', $id)->get();
    }

    /**
     * @param $id
     *
     * @return $this
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function get_by_id()
     */
    public function get_by_id($id)
    {
        return $this->where('custom_values_id', $id)->get();
    }

    /**
     * @param $ids
     *
     * @return null|object
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function get_by_ids()
     */
    public function get_by_ids($ids)
    {
        if (empty($ids)) {
            return;
        }

        $ids = is_array($ids) ? $ids : explode(',', $ids);

        return $this->where_in('custom_values_id', $ids)->get();
    }

    /**
     * @param $fid
     * @param $id
     *
     * @return bool
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function column_has_value()
     */
    public function column_has_value($fid, $id)
    {
        $this->where('custom_field_id', $fid);
        $this->where('custom_values_id', $id);
        $this->get();

        return (bool) ($this->num_rows());
    }

    /**
     * @return $this
     *
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function grouped()
     */
    public function grouped()
    {
        $this->db->select('SQL_CALC_FOUND_ROWS ip_custom_fields.*,ip_custom_values.*', false);
        $this->db->select('count(custom_field_label) as count');
        $this->db->group_by('ip_custom_fields.custom_field_id');

        return $this;
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function default_select()
     */
    public function default_select()
    {
        $this->db->select('ip_custom_fields.*,ip_custom_values.*', false);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function default_join()
     */
    public function default_join()
    {
        $this->db->join('ip_custom_fields', 'ip_custom_values.custom_values_field = ip_custom_fields.custom_field_id', 'inner');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_custom_values.custom_values_value');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/custom_values/models/Mdl_custom_values.php
     * @legacy-function default_group_by()
     */
    public function default_group_by()
    {
        //$this->db->group_by('ip_custom_values.custom_values_field');
    }
}
