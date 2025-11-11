<?php

namespace App\Modules\Invoices\Models;

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
class Template extends CI_Model
{
    /**
     * @param string $type
     *
     * @return array
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/models/Mdl_template.php
     * @legacy-function get_invoice_templates()
     */
    public function get_invoice_templates($type = 'pdf')
    {
        $this->load->helper('directory');

        if ($type == 'pdf') {
            $templates = directory_map(APPPATH . '/views/invoice_templates/pdf', true);
        } elseif ($type == 'public') {
            $templates = directory_map(APPPATH . '/views/invoice_templates/public', true);
        }

        return $this->remove_extension($templates);
    }

    /**
     * @param string $type
     *
     * @return array|mixed
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/models/Mdl_template.php
     * @legacy-function get_quote_templates()
     */
    public function get_quote_templates($type = 'pdf')
    {
        $this->load->helper('directory');

        if ($type == 'pdf') {
            $templates = directory_map(APPPATH . '/views/quote_templates/pdf', true);
        } elseif ($type == 'public') {
            $templates = directory_map(APPPATH . '/views/quote_templates/public', true);
        }

        return $this->remove_extension($templates);
    }

    /**
     * @param $files
     *
     * Legacy migration info:
     * @legacy-file application/modules/invoices/models/Mdl_template.php
     * @legacy-function remove_extension()
     */
    private function remove_extension(array $files): array
    {
        foreach ($files as $key => $file) {
            $files[$key] = str_replace('.php', '', $file);
        }

        return $files;
    }
}
