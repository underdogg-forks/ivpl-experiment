<?php

namespace App\Modules\Welcome\Controllers;

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
class WelcomeController extends CI_Controller
{
    /**
     * Legacy migration info:
     * @legacy-file application/modules/welcome/controllers/Welcome.php
     * @legacy-function index()
     */
    public function index()
    {
        $this->load->model('settings/settings');
        $this->load->helper(['settings', 'echo', 'url']);
        $this->load->view('welcome');
    }
}
