<?php

namespace App\Core;

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
class AdminController extends UserController
{
    public function __construct()
    {
        parent::__construct('user_type', 1);
        $this->setCacheHeaders();
    }

    /**
     * Legacy migration info:
     * @legacy-file application/core/Admin_Controller.php
     * @legacy-function filter_input()
     */
    protected function filter_input(): void
    {
        $input = $this->input->post();

        array_walk(
            $input,
            function (&$value, $key): void {
                if ( ! is_array($value)) {
                    $value = $this->security->xss_clean($value);
                    $value = strip_tags($value);
                    $value = html_escape($value);   // <<<=== that's a CodeIgniter helper
                }
            }
        );
    }

    /**
     * Legacy migration info:
     * @legacy-file application/core/Admin_Controller.php
     * @legacy-function setCacheHeaders()
     */
    protected function setCacheHeaders()
    {
        $this->output
            ->set_header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0')
            ->set_header('Pragma: no-cache')
            ->set_header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');

        $xFrameOptions = env('X_FRAME_OPTIONS');
        if ( ! empty($xFrameOptions)) {
            $this->output->set_header('X-Frame-Options: ' . $xFrameOptions);
        }

        if (env_bool('ENABLE_X_CONTENT_TYPE_OPTIONS', 'true')) {
            $this->output->set_header('X-Content-Type-Options: nosniff');
        }
    }

    /**
     * Render a view as JSON response for AJAX requests
     * 
     * This method captures the view output using output buffering and returns it
     * as a JSON response with a success flag. This standardizes the pattern used
     * across all AJAX controllers for returning HTML content.
     * 
     * @param string $viewPath The path to the view file (e.g., 'invoices/modal_copy_invoice')
     * @param array $data The data array to pass to the view
     * @return void Outputs JSON directly and terminates
     */
    protected function renderViewAsJson(string $viewPath, array $data = []): void
    {
        ob_start();
        $this->layout->load_view($viewPath, $data);
        $html = ob_get_clean();
        
        echo json_encode(['success' => 1, 'html' => $html]);
    }
}
