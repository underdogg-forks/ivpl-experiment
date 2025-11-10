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
class SettingsController extends AdminController
{
    /**
     * Versions constructor.
     */
    public function __construct()
    {
        parent::__construct();

        $this->load->model('settings/versions');
    }

    /**
     * @param int $page
     */
    public function index($page = 0)
    {
        $this->versions->paginate(site_url('versions/index'), $page);
        $versions = $this->versions->result();

        $this->layout->set('versions', $versions);
        $this->layout->buffer('content', 'settings/versions');
        $this->layout->render();
    }
}
