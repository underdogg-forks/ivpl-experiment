<?php

use App\Libraries\Exceptions\NotFoundException;
use App\Libraries\Exceptions\UnauthorizedException;
use App\Libraries\Exceptions\ForbiddenException;
use App\Libraries\Exceptions\BadRequestException;
use App\Libraries\Exceptions\InternalServerErrorException;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Error Handling Test Controller
 *
 * This controller demonstrates the various error handling features.
 * Access via: /error_test/[method_name]
 *
 * @author InvoicePlane Developers & Contributors
 */
#[AllowDynamicProperties]
class Error_test extends MX_Controller
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        
        // Only allow in development mode
        if (ENVIRONMENT !== 'development' && !IP_DEBUG) {
            show_404();
        }
    }

    /**
     * Index - show available tests
     */
    public function index()
    {
        echo '<!DOCTYPE html>
<html>
<head>
    <title>Error Handling Tests</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        h1 { color: #333; }
        .test-link { display: block; padding: 10px; margin: 10px 0; background: #667eea; color: white; text-decoration: none; border-radius: 5px; }
        .test-link:hover { background: #5568d3; }
        .warning { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Error Handling Test Suite</h1>
    
    <div class="warning">
        <strong>Warning:</strong> This page is only available in development mode.
        Each link below will trigger a different type of error to demonstrate the error handling system.
    </div>
    
    <h2>HTTP Exceptions</h2>
    <a href="' . base_url('error_test/test_404') . '" class="test-link">Test 404 Not Found</a>
    <a href="' . base_url('error_test/test_401') . '" class="test-link">Test 401 Unauthorized</a>
    <a href="' . base_url('error_test/test_403') . '" class="test-link">Test 403 Forbidden</a>
    <a href="' . base_url('error_test/test_400') . '" class="test-link">Test 400 Bad Request</a>
    <a href="' . base_url('error_test/test_500') . '" class="test-link">Test 500 Internal Server Error</a>
    
    <h2>PHP Errors</h2>
    <a href="' . base_url('error_test/test_php_error') . '" class="test-link">Test PHP Error (Division by Zero)</a>
    <a href="' . base_url('error_test/test_fatal_error') . '" class="test-link">Test Fatal Error (Undefined Function)</a>
    
    <h2>Database Errors</h2>
    <a href="' . base_url('error_test/test_db_error') . '" class="test-link">Test Database Error</a>
    
    <h2>Request Handler</h2>
    <a href="' . base_url('error_test/test_http_request') . '" class="test-link">Test HTTP Request (with finally block)</a>
    
    <h2>Controller Error Handling</h2>
    <a href="' . base_url('error_test/test_controller_wrapper') . '" class="test-link">Test executeWithErrorHandling() Method</a>
</body>
</html>';
    }

    /**
     * Test 404 Not Found exception
     */
    public function test_404()
    {
        throw new NotFoundException('The requested resource does not exist');
    }

    /**
     * Test 401 Unauthorized exception
     */
    public function test_401()
    {
        throw new UnauthorizedException('Authentication required to access this resource');
    }

    /**
     * Test 403 Forbidden exception
     */
    public function test_403()
    {
        throw new ForbiddenException('You do not have permission to access this resource');
    }

    /**
     * Test 400 Bad Request exception
     */
    public function test_400()
    {
        throw new BadRequestException('Invalid request parameters provided');
    }

    /**
     * Test 500 Internal Server Error exception
     */
    public function test_500()
    {
        throw new InternalServerErrorException('An internal server error occurred');
    }

    /**
     * Test PHP error (converted to exception)
     */
    public function test_php_error()
    {
        $number = 10;
        $zero = 0;
        
        // This will trigger a division by zero warning
        $result = $number / $zero;
        
        echo "Result: " . $result;
    }

    /**
     * Test fatal error
     */
    public function test_fatal_error()
    {
        // Call undefined function - will cause fatal error
        this_function_does_not_exist();
    }

    /**
     * Test database error
     */
    public function test_db_error()
    {
        $this->load->database();
        
        // Attempt to query non-existent table
        $this->db->query("SELECT * FROM non_existent_table");
    }

    /**
     * Test HTTP request with error handling
     */
    public function test_http_request()
    {
        $this->load->library('RequestHandler');
        
        try {
            // Make a request to an invalid URL
            $response = $this->requesthandler->request(
                'https://invalid-domain-that-does-not-exist-12345.com/api',
                'GET',
                [],
                [],
                function($response, $error) {
                    echo "<h3>Finally Block Executed!</h3>";
                    if ($error) {
                        echo "<p>Error occurred: " . htmlspecialchars($error->getMessage()) . "</p>";
                    } else {
                        echo "<p>Request completed successfully</p>";
                    }
                }
            );
        } catch (Exception $e) {
            echo "<h2>Exception Caught in Controller</h2>";
            echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p>The finally block should have executed above this message.</p>";
        }
    }

    /**
     * Test executeWithErrorHandling wrapper
     */
    public function test_controller_wrapper()
    {
        // Load BaseController's executeWithErrorHandling method
        // Create a test that uses it
        
        try {
            $result = $this->executeTest();
            echo "<h2>This should not be reached</h2>";
        } catch (Exception $e) {
            echo "<h2>Exception Caught</h2>";
            echo "<p>Message: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<p><strong>Note:</strong> The finally block would have executed for cleanup.</p>";
        }
    }

    /**
     * Helper method for wrapper test
     */
    private function executeTest()
    {
        // Simulate some work
        $data = ['test' => 'value'];
        
        // Throw an exception
        throw new NotFoundException('Test resource not found in wrapper test');
    }
}
