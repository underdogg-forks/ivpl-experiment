<?php

namespace App\Modules\Email_templates\Controllers;

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
class AjaxController extends \Admin_Controller
{
    public $ajax_controller = true;

    public function get_content()
    {
        $this->load->model('email_templates/emailtemplates');

        $id = $this->input->post('email_template_id');

        echo json_encode($this->emailtemplates->get_by_id($id));
    }
}
