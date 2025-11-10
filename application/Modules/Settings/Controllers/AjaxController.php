<?php

namespace App\Modules\Settings\Controllers;

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
class SettingsAjaxController extends AdminController
{
    public $ajax_controller = true;

    public function get_cron_key()
    {
        $this->load->helper('string');
        echo random_string('alnum', 16);
    }
}
