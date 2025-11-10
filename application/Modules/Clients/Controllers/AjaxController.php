<?php

namespace App\Modules\Clients\Controllers;

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
class AjaxController extends \Admin_Controller
{
    public $ajax_controller = true;

    public function name_query()
    {
        // Load the model & helper
        $this->load->model('clients/clients');

        $response = [];

        // Get the post input
        $query                   = $this->input->get('query');
        $permissiveSearchClients = $this->input->get('permissive_search_clients');

        if (empty($query)) {
            echo json_encode($response);
            exit;
        }

        // Search for chars "in the middle" of clients names
        $moreClientsQuery = $permissiveSearchClients ? '%' : '';

        // Search for clients
        $escapedQuery = $this->db->escape_str($query);
        $escapedQuery = str_replace('%', '', $escapedQuery);

        $clients = $this->clients
            ->where('client_active', 1)
            ->having("client_name LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
            ->or_having("client_surname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
            ->or_having("client_fullname LIKE '" . $moreClientsQuery . $escapedQuery . "%'")
            ->order_by('client_name')
            ->get()
            ->result();

        foreach ($clients as $client) {
            $response[] = [
                'id'   => $client->client_id,
                'text' => htmlsc(format_client($client, false)),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    /**
     * Get the latest clients.
     */
    public function get_latest()
    {
        // Load the model & helper
        $this->load->model('clients/clients');

        $response = [];

        $clients = $this->clients
            ->where('client_active', 1)
            ->limit(5)
            ->order_by('client_date_created')
            ->get()
            ->result();

        foreach ($clients as $client) {
            $response[] = [
                'id'   => $client->client_id,
                'text' => htmlsc(format_client($client, false)),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    public function save_preference_permissive_search_clients()
    {
        $this->load->model('settings/settings');
        $permissiveSearchClients = $this->input->get('permissive_search_clients');

        if ( ! preg_match('!^[0-1]{1}$!', $permissiveSearchClients)) {
            exit;
        }

        $this->settings->save('enable_permissive_search_clients', $permissiveSearchClients);
    }

    /**
     * Delete client note id.
     */
    public function delete_client_note()
    {
        $success        = 0;
        $client_note_id = $this->input->post('client_note_id');
        $this->load->model('clients/clientnotes');

        // Only continue if the note exists or no item id was provided
        if ($this->clientnotes->get_by_id($client_note_id) || empty($client_note_id)) {
            // Delete invoice item
            $this->load->model('clients/clientnotes');
            $item = $this->clientnotes->delete($client_note_id);

            // Check if deletion was successful
            if ($item) {
                $success = 1;
            }
        }

        // Return the response
        echo json_encode([
            'success' => $success,
        ]);
    }

    public function save_client_note()
    {
        $this->load->model('clients/clientnotes');

        if ($this->clientnotes->run_validation()) {
            $this->clientnotes->save();

            $response = [
                'success'   => 1,
                'new_token' => $this->security->get_csrf_hash(),
            ];
        } else {
            $this->load->helper('json_error');
            $response = [
                'success'           => 0,
                'new_token'         => $this->security->get_csrf_hash(),
                'validation_errors' => json_errors(),
            ];
        }

        echo json_encode($response);
    }

    public function load_client_notes()
    {
        $this->load->model('clients/clientnotes');
        $data = [
            'client_notes' => $this->clientnotes->where(
                'client_id',
                $this->input->post('client_id')
            )->get()->result(),
        ];

        $this->layout->load_view('clients/partial_notes', $data);
    }
}
