# Global Error Handling System

This document describes the comprehensive error handling system implemented in InvoicePlane.

## Overview

The application now includes a global error handling system that wraps all requests (incoming and outgoing) in try/catch/finally blocks, provides beautiful error pages in development, user-friendly error pages in production, and comprehensive error logging.

## Components

### 1. Exception Handler (`application/hooks/ExceptionHandler.php`)

The global exception handler is initialized as a pre-system hook and handles:
- PHP errors (converted to exceptions)
- Uncaught exceptions
- Fatal errors during shutdown

**Features:**
- Automatic error logging to `application/logs/exceptions-YYYY-MM-DD.php`
- Whoops integration for development (beautiful error pages with stack traces)
- User-friendly error pages for production
- HTTP status code handling (404, 401, 403, 500, etc.)
- Cleanup operations in finally blocks

### 2. HTTP Exception Classes (`application/Libraries/Exceptions/`)

Custom exception classes for proper HTTP error handling:
- `HttpException` - Base class for all HTTP exceptions
- `NotFoundException` (404)
- `UnauthorizedException` (401)
- `ForbiddenException` (403)
- `BadRequestException` (400)
- `InternalServerErrorException` (500)

**Usage Example:**
```php
use App\Libraries\Exceptions\NotFoundException;

if (!$invoice) {
    throw new NotFoundException('Invoice not found');
}
```

### 3. Enhanced Base Controller

The `BaseController` now includes:
- Request logging (logs all incoming requests when `IP_DEBUG` is enabled)
- Response logging (tracks response times and status codes)
- `executeWithErrorHandling()` method for wrapping controller actions
- Destructor cleanup for database connections

**Usage Example:**
```php
public function view($id)
{
    return $this->executeWithErrorHandling(function() use ($id) {
        // Your controller logic here
        $invoice = $this->mdl_invoices->find($id);
        
        if (!$invoice) {
            throw new NotFoundException('Invoice not found');
        }
        
        return $this->load->view('invoice_view', ['invoice' => $invoice]);
    }, function() {
        // Optional cleanup in finally block
        // This runs whether an exception occurred or not
    });
}
```

### 4. Request Handler Library (`application/Libraries/RequestHandler.php`)

Provides wrapped HTTP requests with comprehensive error handling:

**Features:**
- Try/catch/finally wrapper for all HTTP requests
- Automatic logging of outgoing and incoming requests
- Error logging for failed requests
- Custom cleanup callbacks

**Usage Example:**
```php
$this->load->library('RequestHandler');

try {
    $response = $this->requesthandler->request(
        'https://api.example.com/data',
        'POST',
        ['key' => 'value'],
        ['Authorization: Bearer token'],
        function($response, $error) {
            // Cleanup callback (finally block)
            if ($error) {
                // Handle cleanup after error
            }
        }
    );
    
    if ($response['success']) {
        // Process successful response
        $data = $response['data'];
    }
} catch (Exception $e) {
    // Handle error
    log_message('error', 'API request failed: ' . $e->getMessage());
}
```

### 5. Bootstrap Try/Catch Wrapper

Both `index.php` and `public/index.php` wrap the entire CodeIgniter bootstrap in a try/catch/finally block:

**Features:**
- Catches any exceptions during application bootstrap
- Ensures proper cleanup even if the application crashes
- Fallback error display if exception handler is not available

### 6. Custom Error Pages

**Development Mode** (when `ENVIRONMENT === 'development'` or `IP_DEBUG === true`):
- Uses Whoops for beautiful error pages with interactive stack traces
- Shows full exception details, file paths, and line numbers
- Displays request context (method, URI, host, etc.)

**Production Mode**:
- Shows user-friendly error pages with appropriate HTTP status codes
- Hides sensitive information (stack traces, file paths)
- Modern, responsive design
- Link back to home page

Custom error template: `application/errors/error_exception.php`

## Configuration

### Enable Debug Mode

In your `ipconfig.php`:
```php
ENABLE_DEBUG=true
```

This will:
- Enable Whoops error pages
- Log all requests and responses
- Show detailed error messages

### Disable Debug Mode (Production)

```php
ENABLE_DEBUG=false
```

This will:
- Show user-friendly error pages
- Hide sensitive information
- Log errors without exposing details to users

## Error Logging

### Exception Logs
Location: `application/logs/exceptions-YYYY-MM-DD.php`

Contains:
- Timestamp
- Exception class
- Error message
- File and line number
- Full stack trace

### Request Logs (Debug Mode Only)
Location: `application/logs/requests-YYYY-MM-DD.php`

Contains:
- Incoming request details (method, URI, IP)
- Response status codes
- Request duration

### HTTP Error Logs
Location: `application/logs/http-errors-YYYY-MM-DD.php`

Contains:
- Failed HTTP requests (outgoing)
- Error messages
- Request details

## Best Practices

### 1. Use HTTP Exceptions

Instead of generic exceptions, use specific HTTP exceptions:

```php
// Bad
if (!$item) {
    throw new Exception('Not found');
}

// Good
if (!$item) {
    throw new NotFoundException('The requested item was not found');
}
```

### 2. Wrap Controller Actions

Use `executeWithErrorHandling()` for controller methods:

```php
public function create()
{
    return $this->executeWithErrorHandling(function() {
        // Your logic here
    }, function() {
        // Cleanup here (optional)
    });
}
```

### 3. Log Important Events

Use CodeIgniter's logging in your catch blocks:

```php
try {
    // Risky operation
} catch (Exception $e) {
    log_message('error', 'Failed to process invoice: ' . $e->getMessage());
    throw $e; // Re-throw for global handler
}
```

### 4. Use Finally for Cleanup

Always use finally blocks for cleanup operations:

```php
try {
    $file = fopen('data.txt', 'r');
    // Process file
} catch (Exception $e) {
    log_message('error', $e->getMessage());
} finally {
    if (isset($file) && is_resource($file)) {
        fclose($file);
    }
}
```

### 5. Return Meaningful Error Messages

For API endpoints, return structured error responses:

```php
try {
    // Process API request
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code($e->getStatusCode() ?? 500);
    echo json_encode([
        'error' => true,
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
    ]);
    exit;
}
```

## Testing Error Handling

### Test Exception Handling

Create a test controller to verify error handling:

```php
public function test_error()
{
    throw new \Exception('This is a test exception');
}
```

Visit the URL and verify:
- Development: Shows Whoops error page
- Production: Shows custom error page

### Test HTTP Exceptions

```php
public function test_404()
{
    throw new \App\Libraries\Exceptions\NotFoundException('Test 404');
}
```

Verify:
- HTTP 404 status code is returned
- Appropriate error page is displayed

### Test Request Logging

With `IP_DEBUG=true`:
1. Make a request to any page
2. Check `application/logs/requests-YYYY-MM-DD.php`
3. Verify request details are logged

## Troubleshooting

### Whoops Not Displaying

1. Check that `filp/whoops` is installed: `composer install`
2. Verify `ENVIRONMENT === 'development'` or `IP_DEBUG === true`
3. Check that Whoops is autoloaded: `composer dump-autoload`

### Errors Not Being Logged

1. Check that `application/logs/` directory exists
2. Verify directory is writable: `chmod 777 application/logs/`
3. Ensure `IP_DEBUG` is enabled for request logging

### Custom Error Page Not Showing

1. Verify `application/errors/error_exception.php` exists
2. Check file permissions
3. Ensure `ENVIRONMENT` is set correctly

## Dependencies

Required Composer packages:
- `filp/whoops` (^2.18) - Beautiful error pages for development
- `illuminate/support` (^10.0) - Laravel components for enhanced functionality

Already included in `composer.json`.

## Migration from Old Error Handling

The new system is backward compatible. Existing error handling will continue to work, but you can gradually:

1. Replace generic exceptions with HTTP exceptions
2. Wrap controller methods with `executeWithErrorHandling()`
3. Add proper error logging
4. Use the RequestHandler for HTTP requests

## Examples

### Example 1: Invoice Controller with Error Handling

```php
<?php

use App\Libraries\Exceptions\NotFoundException;
use App\Libraries\Exceptions\ForbiddenException;

class Invoices extends Admin_Controller
{
    public function view($id)
    {
        return $this->executeWithErrorHandling(function() use ($id) {
            // Check if invoice exists
            $invoice = $this->mdl_invoices->find($id);
            
            if (!$invoice) {
                throw new NotFoundException("Invoice #{$id} not found");
            }
            
            // Check permissions
            if (!$this->can_view_invoice($invoice)) {
                throw new ForbiddenException("You don't have permission to view this invoice");
            }
            
            // Load the view
            $this->load->view('invoices/view', ['invoice' => $invoice]);
        });
    }
}
```

### Example 2: API Request with Error Handling

```php
<?php

public function sync_with_external_api()
{
    $this->load->library('RequestHandler');
    
    try {
        $response = $this->requesthandler->request(
            'https://api.example.com/invoices',
            'POST',
            ['invoice_data' => $this->prepare_invoice_data()],
            ['Authorization: Bearer ' . $this->api_token],
            function($response, $error) {
                // Cleanup: log the attempt
                log_message('info', 'API sync attempt completed');
            }
        );
        
        if ($response['success']) {
            $this->session->set_flashdata('alert_success', 'Successfully synced with API');
        }
    } catch (Exception $e) {
        $this->session->set_flashdata('alert_error', 'API sync failed: ' . $e->getMessage());
    }
    
    redirect('invoices');
}
```

## Summary

The global error handling system provides:

✅ Try/catch wrapper for all requests (incoming and outgoing)  
✅ Beautiful Whoops error pages in development  
✅ User-friendly error pages in production  
✅ Comprehensive error logging  
✅ HTTP exception classes for proper status codes  
✅ Finally blocks for guaranteed cleanup  
✅ Request/response logging for debugging  
✅ Backward compatibility with existing code  

The system is production-ready and follows modern PHP error handling best practices while maintaining compatibility with the CodeIgniter 3 framework.
