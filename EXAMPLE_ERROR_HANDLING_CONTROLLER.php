<?php

namespace App\Modules\Examples\Controllers;

use App\Core\AdminController;
use App\Libraries\Exceptions\NotFoundException;
use App\Libraries\Exceptions\BadRequestException;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Example Controller with Error Handling
 *
 * This demonstrates how to use the error handling system in a real controller.
 *
 * @author InvoicePlane Developers & Contributors
 */
class ExampleController extends AdminController
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->model('invoices/mdl_invoices');
    }

    /**
     * View an invoice with proper error handling
     *
     * @param int $id Invoice ID
     */
    public function view($id)
    {
        // Use the executeWithErrorHandling wrapper for automatic try/catch/finally
        return $this->executeWithErrorHandling(
            function () use ($id) {
                // Validate input
                if (!is_numeric($id) || $id <= 0) {
                    throw new BadRequestException('Invalid invoice ID provided');
                }

                // Find the invoice
                $invoice = $this->mdl_invoices->find($id);

                // Check if exists
                if (!$invoice) {
                    throw new NotFoundException("Invoice #{$id} was not found");
                }

                // Check permissions (example)
                if (!$this->hasPermissionToView($invoice)) {
                    throw new ForbiddenException('You do not have permission to view this invoice');
                }

                // Load the view
                $this->layout->buffer('content', 'invoices/view', [
                    'invoice' => $invoice,
                ]);

                $this->layout->render();
            },
            function () {
                // Cleanup in finally block
                // This runs whether an exception occurred or not
                
                // Example: Close any open resources
                // Example: Log the request completion
                log_message('debug', 'Invoice view request completed');
            }
        );
    }

    /**
     * Make an API call with error handling
     */
    public function syncWithExternalApi()
    {
        $this->load->library('RequestHandler');

        try {
            // Make the API request with automatic error handling
            $response = $this->requesthandler->request(
                'https://api.example.com/invoices/sync',
                'POST',
                [
                    'invoices' => $this->getInvoicesToSync(),
                ],
                [
                    'Authorization: Bearer ' . $this->getApiToken(),
                    'Content-Type: application/json',
                ],
                function ($response, $error) {
                    // Finally block - always executes
                    log_message('info', 'API sync attempt completed');
                    
                    // Log the result
                    if ($error) {
                        log_message('error', 'API sync failed: ' . $error->getMessage());
                    } else {
                        log_message('info', 'API sync successful');
                    }
                }
            );

            // Process successful response
            if ($response['success']) {
                $this->session->set_flashdata('alert_success', 'Successfully synced with external API');
            }
        } catch (\Exception $e) {
            // Handle the error
            $this->session->set_flashdata('alert_error', 'API sync failed: ' . $e->getMessage());
        }

        redirect('invoices');
    }

    /**
     * Example: Check if user has permission to view invoice
     *
     * @param object $invoice
     * @return bool
     */
    private function hasPermissionToView($invoice)
    {
        // Implement your permission logic here
        // This is just an example
        return true;
    }

    /**
     * Example: Get invoices to sync
     *
     * @return array
     */
    private function getInvoicesToSync()
    {
        // Implement your logic here
        return [];
    }

    /**
     * Example: Get API token
     *
     * @return string
     */
    private function getApiToken()
    {
        // Implement your logic here
        return '';
    }
}
