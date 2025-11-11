<?php

namespace App\Modules\Users\Controllers;

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
class UsersAjaxController extends AdminController
{
    public $ajax_controller = true;

    /**
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function name_query()
     */
    public function name_query($type = 1)
    {
        // Load the model & helper
        $this->load->model('users/user');
        $this->load->helper('user');

        $response = [];

        // Get the post input
        $query                 = $this->input->get('query');
        $permissiveSearchUsers = $this->input->get('permissive_search_users');

        if (empty($query)) {
            echo json_encode($response);
            exit;
        }

        // Search for chars "in the middle" of users names
        $moreUsersQuery = $permissiveSearchUsers ? '%' : '';

        // Search for users $type
        $escapedQuery = $this->db->escape_str($query);
        $escapedQuery = str_replace('%', '', $escapedQuery);
        // Not searched: user_address_1 user_address_2 user_city user_state user_zip user_country user_invoicing_contact
        $users = $this->user
            ->where('user_active', 1)
            ->where('user_type', $type)
            ->having("user_name LIKE '" . $moreUsersQuery . $escapedQuery . "%'")
            ->or_having("user_company LIKE '" . $moreUsersQuery . $escapedQuery . "%'")
            ->or_having("user_invoicing_contact LIKE '" . $moreUsersQuery . $escapedQuery . "%'")
            ->order_by('user_name')
            ->get()
            ->result();

        foreach ($users as $user) {
            $response[] = [
                'id'   => $user->user_id,
                'text' => format_user($user),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    /**
     * Get the latest users.
     *
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function get_latest()
     */
    public function get_latest()
    {
        // Load the model & helper
        $this->load->model('users/user');

        $response = [];

        $users = $this->user
            ->where('user_active', 1)
            ->limit(5)
            ->order_by('user_date_created')
            ->get()
            ->result();

        foreach ($users as $user) {
            $response[] = [
                'id'   => $user->user_id,
                'text' => htmlsc(format_user($user)),
            ];
        }

        // Return the results
        echo json_encode($response);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function save_preference_permissive_search_users()
     */
    public function save_preference_permissive_search_users()
    {
        $this->load->model('settings/setting');
        $permissiveSearchUsers = $this->input->get('permissive_search_users');

        if ( ! preg_match('!^[0-1]{1}$!', $permissiveSearchUsers)) {
            exit;
        }

        $this->setting->save('enable_permissive_search_users', $permissiveSearchUsers);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function save_user_client()
     */
    public function save_user_client()
    {
        $user_id   = $this->input->post('user_id');
        $client_id = $this->input->post('client_id');

        $this->load->model('clients/client');
        $this->load->model('user_clients/userclient');

        $client = $this->client->get_by_id($client_id);
        if ($client) {
            $client_id = $client->client_id;

            // Is this a new user or an existing user?
            if ( ! empty($user_id)) {
                // Existing user - go ahead and save the entries
                $user_client = $this->userclients->where('ip_user_clients.user_id', $user_id)
                    ->where('ip_user_clients.client_id', $client_id)->get();

                if ( ! $user_client->num_rows()) {
                    $this->userclients->save(null, ['user_id' => $user_id, 'client_id' => $client_id]);
                }
            } else {
                // New user - assign the entries to a session variable until user record is saved
                $user_clients = $this->session->userdata('user_clients') ? $this->session->userdata('user_clients') : [];

                $user_clients[$client_id] = $client_id;

                $this->session->set_userdata('user_clients', $user_clients);
            }
        }
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function load_user_client_table()
     */
    public function load_user_client_table()
    {
        $session_user_clients = $this->session->userdata('user_clients');

        if ($session_user_clients) {
            $this->load->model('clients/client');

            $data = [
                'id'           => null,
                'user_clients' => $this->client->where_in('ip_clients.client_id', $session_user_clients)->get()->result(),
            ];
        } else {
            $this->load->model('user_clients/userclient');

            $data = [
                'id'           => $this->input->post('user_id'),
                'user_clients' => $this->userclients->where('ip_user_clients.user_id', $this->input->post('user_id'))->get()->result(),
            ];
        }

        // Capture view output and return as JSON
        ob_start();
        $this->layout->load_view('users/partial_user_client_table', $data);
        $html = ob_get_clean();
        
        echo json_encode(['success' => 1, 'html' => $html]);
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/users/controllers/Ajax.php
     * @legacy-function modal_add_user_client()
     */
    public function modal_add_user_client($user_id = null)
    {
        $this->load->model('clients/client');

        if ($session_user_clients = $this->session->userdata('user_clients')) {
            $clients          = $this->client->where_not_in('ip_clients.client_id', $session_user_clients)->get()->result();
            $assigned_clients = [];
        } else {
            $this->load->model('user_clients/userclient');
            $assigned_clients_query = $this->userclients->where('ip_user_clients.user_id', $user_id)->get()->result();
            $assigned_clients       = [];

            foreach ($assigned_clients_query as $assigned_client) {
                $assigned_clients[] = (int) $assigned_client->client_id;
            }

            if ($assigned_clients === []) {
                $clients = $this->client->get()->result();
            } else {
                $clients = $this->client->where_not_in('ip_clients.client_id', $assigned_clients)->get()->result();
            }
        }

        $data = [
            'user_id' => $user_id,
            'clients' => $clients,
        ];

        // Capture view output and return as JSON
        ob_start();
        $this->layout->load_view('users/modal_user_client', $data);
        $html = ob_get_clean();
        
        echo json_encode(['success' => 1, 'html' => $html]);
    }
}
