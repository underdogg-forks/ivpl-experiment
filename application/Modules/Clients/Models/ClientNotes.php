<?php

namespace App\Modules\Clients\Models;

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
class ClientNotes extends \Response_Model
{
    public $table = 'ip_client_notes';

    public $primary_key = 'ip_client_notes.client_note_id';

    /**
     * Legacy migration info:
     * @legacy-file application/modules/clients/models/Mdl_client_notes.php
     * @legacy-function default_order_by()
     */
    public function default_order_by()
    {
        $this->db->order_by('ip_client_notes.client_note_date DESC');
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/clients/models/Mdl_client_notes.php
     * @legacy-function validation_rules()
     */
    public function validation_rules()
    {
        return [
            'client_id' => [
                'field' => 'client_id',
                'label' => trans('client'),
                'rules' => 'required',
            ],
            'client_note' => [
                'field' => 'client_note',
                'label' => trans('note'),
                'rules' => 'required',
            ],
        ];
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/clients/models/Mdl_client_notes.php
     * @legacy-function db_array()
     */
    public function db_array()
    {
        $db_array = parent::db_array();

        $db_array['client_note_date'] = date('Y-m-d');

        return $db_array;
    }

    /**
     * @param int $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/clients/models/Mdl_client_notes.php
     * @legacy-function delete()
     */
    public function delete($id): bool
    {
        parent::delete($id);

        // For Ajax Check if deletion was successful
        return true;
    }
}
