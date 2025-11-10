<?php

namespace App\Modules\Users\Controllers;

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
class UsersController extends \Admin_Controller
{
    /**
     * Users constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('users/users');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->users->paginate(site_url('users/index'), $page);
        $users = $this->users->result();

        $this->layout->set(
            [
                'filter_display'     => true,
                'filter_placeholder' => trans('filter_users'),
                'filter_method'      => 'filter_users',
                'users'              => $users,
                'user_types'         => $this->users->user_types(),
            ]
        );
        $this->layout->buffer('content', 'users/index');
        $this->layout->render();
    }

    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('users');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->users->run_validation(($id) ? 'validation_rules_existing' : 'validation_rules')) {
            $id = $this->users->save($id);

            $this->load->model('custom_fields/usercustom');
            $this->usercustom->save_custom($id, $this->input->post('custom'));

            // Update the session details if the logged in user edited his account
            if ($this->session->userdata('user_id') == $id) {
                $new_details = $this->users->get_by_id($id);

                $session_data = [
                    'user_type'     => $new_details->user_type,
                    'user_id'       => $new_details->user_id,
                    'user_name'     => $new_details->user_name,
                    'user_email'    => $new_details->user_email,
                    'user_company'  => $new_details->user_company,
                    'user_language' => $new_details->user_language ?? 'system',
                ];

                $this->session->set_userdata($session_data);
            }

            $this->session->unset_userdata('user_clients');

            redirect('users');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->users->prep_form($id)) {
                show_404();
            }

            $this->load->model('custom_fields/usercustom');

            $user_custom = $this->usercustom->where('user_id', $id)->get();

            if ($user_custom->num_rows()) {
                $user_custom = $user_custom->row();

                unset($user_custom->user_id, $user_custom->user_custom_id);

                foreach ($user_custom as $key => $val) {
                    $this->users->set_form_value('custom[' . $key . ']', $val);
                }
            }
        } elseif ($this->input->post('btn_submit')) {
            if ($this->input->post('custom')) {
                foreach ($this->input->post('custom') as $key => $val) {
                    $this->users->set_form_value('custom[' . $key . ']', $val);
                }
            }
        }

        $this->load->helper(['custom_values', 'e-invoice']);
        $this->load->model(
            [
                'user_clients/mdl_user_clients',
                'clients/mdl_clients',
                'custom_fields/mdl_custom_fields',
                'custom_fields/mdl_user_custom',
                'custom_values/mdl_custom_values',
            ]
        );

        $custom_fields['ip_user_custom'] = $this->customfields->by_table('ip_user_custom')->get()->result();
        $custom_values                   = [];
        foreach ($custom_fields['ip_user_custom'] as $custom_field) {
            if (in_array($custom_field->custom_field_type, $this->customvalues->custom_value_fields())) {
                $values                                        = $this->customvalues->get_by_fid($custom_field->custom_field_id)->result();
                $custom_values[$custom_field->custom_field_id] = $values;
            }
        }

        $fields = $this->usercustom->get_by_useid($id);

        foreach ($custom_fields['ip_user_custom'] as $cfield) {
            foreach ($fields as $fvalue) {
                if ($fvalue->user_custom_fieldid == $cfield->custom_field_id) {
                    // TODO: Hackish, may need a better optimization
                    $this->users->set_form_value(
                        'custom[' . $cfield->custom_field_id . ']',
                        $fvalue->user_custom_fieldvalue
                    );
                    break;
                }
            }
        }

        // Need in remittance text tags selector (template-tags-invoices)
        $custom_fields['ip_invoice_custom'] = $this->customfields->by_table('ip_invoice_custom')->get()->result();

        $this->layout->set(
            [
                'id'               => $id,
                'user_types'       => $this->users->user_types(),
                'user_clients'     => $this->userclients->where('ip_user_clients.user_id', $id)->get()->result(),
                'custom_fields'    => $custom_fields,
                'custom_values'    => $custom_values,
                'countries'        => get_country_list(trans('cldr')),
                'selected_country' => $this->users->form_value('user_country') ?: get_setting('default_country'),
                'clients'          => $this->clients->where('client_active', 1)->get()->result(),
                'languages'        => get_available_languages(),
                'einvoicing'       => get_setting('einvoicing'),
            ]
        );

        $this->layout->buffer('content', 'users/form');
        $this->layout->render();
    }

    /**
     * @param $user_id
     */
    public function change_password(string $user_id)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('users');
        }

        if ($this->users->run_validation('validation_rules_change_password')) {
            $this->users->save_change_password($user_id, $this->input->post('user_password'));
            redirect('users/form/' . $user_id);
        }

        $this->layout->buffer('content', 'users/form_change_password');
        $this->layout->render();
    }

    /**
     * @param $id
     */
    public function delete($id)
    {
        if ($id != 1) {
            $this->users->delete($id);
        }

        redirect('users');
    }

    /**
     * @param $user_id
     * @param $user_client_id
     */
    public function delete_user_client(string $user_id, $user_client_id)
    {
        $this->load->model('user_clients/userclients');

        $this->userclients->delete($user_client_id);

        redirect('users/form/' . $user_id);
    }
}
