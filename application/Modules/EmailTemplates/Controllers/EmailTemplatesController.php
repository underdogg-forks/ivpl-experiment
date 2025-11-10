<?php

namespace App\Modules\EmailTemplates\Controllers;

use App\Core\AdminController;

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
class EmailTemplatesController extends AdminController
{
    /**
     * Email_Templates constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('email_templates/emailtemplate');
    }

    /**
     * @param int $page
     *
     * Legacy migration info:
     * @legacy-file application/modules/email_templates/controllers/EmailTemplates.php
     * @legacy-function index()
     */
    public function index($page = 0)
    {
        $this->emailtemplates->paginate(site_url('email_templates/index'), $page);
        $email_templates = $this->emailtemplates->result();

        $this->layout->set('email_templates', $email_templates);
        $this->layout->buffer('content', 'email_templates/index');
        $this->layout->render();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/modules/email_templates/controllers/EmailTemplates.php
     * @legacy-function form()
     */
    public function form($id = null)
    {
        if ($this->input->post('btn_cancel')) {
            redirect('email_templates');
        }

        $this->filter_input();  // <<<--- filters _POST array for nastiness

        if ($this->input->post('is_update') == 0 && $this->input->post('email_template_title') != '') {
            $check = $this->db->get_where('ip_email_templates', ['email_template_title' => $this->input->post('email_template_title')])->result();
            if ( ! empty($check)) {
                $this->session->set_flashdata('alert_error', trans('email_template_already_exists'));
                redirect('email_templates/form');
            }
        }

        if ($this->emailtemplates->run_validation()) {
            $this->emailtemplates->save($id);
            redirect('email_templates');
        }

        if ($id && ! $this->input->post('btn_submit')) {
            if ( ! $this->emailtemplates->prep_form($id)) {
                show_404();
            }

            $this->emailtemplates->set_form_value('is_update', true);
        }

        $this->load->model([
            'custom_fields/mdl_custom_field',
            'invoices/mdl_template',
        ]);

        foreach (array_keys($this->customfields->custom_tables()) as $table) {
            $custom_fields[$table] = $this->customfields->by_table($table)->get()->result();
        }

        $this->layout->set([
            'custom_fields'         => $custom_fields,
            'invoice_templates'     => $this->template->get_invoice_templates(),
            'quote_templates'       => $this->template->get_quote_templates(),
            'selected_pdf_template' => $this->emailtemplates->form_value('email_template_pdf_template'),
        ]);
        $this->layout->buffer('content', 'email_templates/form');
        $this->layout->render();
    }

    /**
     * @param $id
     *
     * Legacy migration info:
     * @legacy-file application/modules/email_templates/controllers/EmailTemplates.php
     * @legacy-function delete()
     */
    public function delete($id)
    {
        $this->emailtemplates->delete($id);
        redirect('email_templates');
    }
}
