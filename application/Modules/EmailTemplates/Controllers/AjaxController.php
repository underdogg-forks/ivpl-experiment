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
class EmailTemplatesAjaxController extends AdminController
{
    public $ajax_controller = true;

    /**
     * Legacy migration info:
     * @legacy-file application/modules/email_templates/controllers/Ajax.php
     * @legacy-function get_content()
     */
    public function get_content()
    {
        $this->load->model('email_templates/emailtemplate');

        $id = $this->input->post('email_template_id');

        echo json_encode($this->emailtemplates->get_by_id($id));
    }
}
