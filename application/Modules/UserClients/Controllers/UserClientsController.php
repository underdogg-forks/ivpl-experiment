<?php

namespace App\Modules\UserClients\Controllers;

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
class User_Clients extends AdminController
{
    /**
     * Custom_Values constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('users/user');
        $this->load->model('clients/client');
        $this->load->model('user_clients/userclients');
    }

    public function index()
    {
        redirect('users');
    }

    public function user($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('users');
        }

        $user = $this->user->get_by_id($id);

        if (empty($user)) {
            redirect('users');
        }

        $user_clients = $this->userclients->assigned_to($id)->get()->result();

        $this->layout->set(
            [
                'user'         => $user,
                'user_clients' => $user_clients,
            ]
        );
        $this->layout->set('id', $id);
        $this->layout->buffer('content', 'user_clients/field');
        $this->layout->render();
    }

    public function create($user_id = null)
    {
        if ( ! $user_id) {
            redirect('custom_values');
        } elseif ($this->input->post('btn_cancel')) {
            redirect('user_clients/field/' . $user_id);
        }

        if ($this->userclients->run_validation()) {
            if ($this->input->post('user_all_clients')) {
                $users_id = [$user_id];

                $this->userclients->set_all_clients_user($users_id);

                $user_update = ['user_all_clients' => 1];
            } else {
                $user_update = ['user_all_clients' => 0];

                $this->userclients->save();
            }

            $this->db->where('user_id', $user_id);
            $this->db->update('ip_users', $user_update);

            redirect('user_clients/user/' . $user_id);
        }

        $user    = $this->user->get_by_id($user_id);
        $clients = $this->client->get_not_assigned_to_user($user_id);

        $this->layout->set(
            [
                'id'      => $user_id,
                'user'    => $user,
                'clients' => $clients,
            ]
        );
        $this->layout->buffer('content', 'user_clients/new');
        $this->layout->render();
    }

    /**
     * @param int $user_client_id
     */
    public function delete($user_client_id)
    {
        $ref = $this->userclients->get_by_id($user_client_id);

        $this->userclients->delete($user_client_id);
        redirect('user_clients/user/' . $ref->user_id);
    }
}
