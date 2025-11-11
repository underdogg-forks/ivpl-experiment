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
class BaseController extends \MX_Controller
{
    /** @var bool */
    public $ajax_controller = false;

    /** @var array Request log for tracking */
    protected $requestLog = [];

    /**
     * Base_Controller constructor.
     */
    public function __construct()
    {
        parent::__construct();

        // Log incoming request
        $this->logIncomingRequest();

        $this->config->load('invoice_plane');

        // Don't allow non-ajax requests to ajax controllers
        if ($this->ajax_controller && ! $this->input->is_ajax_request()) {
            exit;
        }

        $this->load->helper('url');

        // Globally disallow GET requests to delete methods
        if (mb_strstr(current_url(), 'delete') && $this->input->method() !== 'post') {
            show_404();
        }

        // Load basic stuff
        $this->load->library('session');
        $this->load->helper('redirect');

        // Check if database has been configured
        if ( ! env_bool('SETUP_COMPLETED')) {
            redirect('/welcome');
        } else {
            $this->load->library(['encryption', 'form_validation', 'session', 'ClientTitleEnum']);
            $this->load->database();

            $this->load->helper(['trans', 'number', 'pager', 'invoice', 'date', 'form', 'echo', 'user', 'client', 'country']);

            // Load setting model and load settings
            $this->load->model('settings/setting');
            if ($this->setting != null) {
                $this->setting->load_settings();
            }

            $this->load->helper('settings');

            // Load the language based on user config, fall back to system if needed
            $user_lang = $this->session->userdata('user_language');
            if (empty($user_lang) || $user_lang == 'system') {
                set_language(get_setting('default_language'));
            } else {
                set_language($user_lang);
            }

            $this->load->helper('language');

            // Load the layout module to start building the app
            $this->load->module('layout');
        }
    }

    /**
     * Execute a controller method with try/catch/finally wrapper
     *
     * @param callable $callback The method to execute
     * @param callable|null $finally Optional finally callback
     * @return mixed
     * @throws \Exception
     */
    protected function executeWithErrorHandling($callback, $finally = null)
    {
        try {
            return $callback();
        } catch (\Exception $e) {
            // Log the error
            log_message('error', sprintf(
                'Exception in %s::%s - %s',
                get_class($this),
                debug_backtrace()[1]['function'] ?? 'unknown',
                $e->getMessage()
            ));

            // Re-throw for global handler
            throw $e;
        } finally {
            // Execute cleanup
            if ($finally !== null && is_callable($finally)) {
                $finally();
            }

            // Log outgoing response
            $this->logOutgoingResponse();
        }
    }

    /**
     * Log incoming request details
     */
    protected function logIncomingRequest()
    {
        $this->requestLog = [
            'timestamp' => microtime(true),
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN',
            'uri' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ];

        // Log to file if enabled
        if (defined('IP_DEBUG') && IP_DEBUG) {
            $logFile = LOGS_FOLDER . 'requests-' . date('Y-m-d') . '.php';
            $logEntry = sprintf(
                "[%s] %s %s from %s\n",
                date('Y-m-d H:i:s'),
                $this->requestLog['method'],
                $this->requestLog['uri'],
                $this->requestLog['ip']
            );

            if (!file_exists($logFile)) {
                $logEntry = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n" . $logEntry;
            }

            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Log outgoing response details
     */
    protected function logOutgoingResponse()
    {
        if (!empty($this->requestLog) && defined('IP_DEBUG') && IP_DEBUG) {
            $duration = microtime(true) - $this->requestLog['timestamp'];
            $statusCode = http_response_code();

            $logFile = LOGS_FOLDER . 'requests-' . date('Y-m-d') . '.php';
            $logEntry = sprintf(
                "[%s] Response: %d - Duration: %.4fs\n",
                date('Y-m-d H:i:s'),
                $statusCode,
                $duration
            );

            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Destructor - cleanup and logging
     */
    public function __destruct()
    {
        // Final cleanup in finally-style pattern
        try {
            // Any final operations
            if (isset($this->db) && is_object($this->db)) {
                // Database connection will be closed automatically by CodeIgniter
            }
        } catch (\Exception $e) {
            // Suppress destructor exceptions
            log_message('error', 'Exception in destructor: ' . $e->getMessage());
        }
    }
}
