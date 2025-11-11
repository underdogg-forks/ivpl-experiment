<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * Global Exception Handler
 *
 * This class handles all exceptions and errors throughout the application,
 * providing beautiful error pages in development and user-friendly pages in production.
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class ExceptionHandler
{
    /**
     * Initialize the exception handler
     */
    public function init()
    {
        // Register error handler
        set_error_handler([$this, 'handleError']);
        
        // Register exception handler
        set_exception_handler([$this, 'handleException']);
        
        // Register shutdown handler for fatal errors
        register_shutdown_function([$this, 'handleShutdown']);
    }

    /**
     * Handle PHP errors
     *
     * @param int $errno Error level
     * @param string $errstr Error message
     * @param string $errfile Error file
     * @param int $errline Error line
     * @return bool
     */
    public function handleError($errno, $errstr, $errfile, $errline)
    {
        // Don't handle errors that are suppressed with @
        if (!(error_reporting() & $errno)) {
            return false;
        }

        // Convert error to exception
        $exception = new ErrorException($errstr, 0, $errno, $errfile, $errline);
        $this->handleException($exception);
        
        return true;
    }

    /**
     * Handle uncaught exceptions
     *
     * @param Throwable $exception
     */
    public function handleException($exception)
    {
        try {
            // Log the exception
            $this->logException($exception);

            // Determine if we're in development mode
            $isDevelopment = (ENVIRONMENT === 'development' || IP_DEBUG);

            if ($isDevelopment && class_exists('Whoops\Run')) {
                $this->renderWhoops($exception);
            } else {
                $this->renderProductionError($exception);
            }
        } catch (Throwable $e) {
            // Fallback error handler
            $this->renderFallbackError($exception, $e);
        } finally {
            // Always clean up resources
            $this->cleanup();
        }
    }

    /**
     * Handle fatal errors on shutdown
     */
    public function handleShutdown()
    {
        $error = error_get_last();
        
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $exception = new ErrorException(
                $error['message'],
                0,
                $error['type'],
                $error['file'],
                $error['line']
            );
            
            $this->handleException($exception);
        }
    }

    /**
     * Render error using Whoops in development
     *
     * @param Throwable $exception
     */
    protected function renderWhoops($exception)
    {
        // Check if Whoops is actually available
        if (!class_exists('Whoops\Run') || !class_exists('Whoops\Handler\PrettyPageHandler')) {
            // Fallback to development error page without Whoops
            $this->renderDevelopmentError($exception);
            return;
        }

        $whoops = new Whoops\Run();
        $handler = new Whoops\Handler\PrettyPageHandler();
        
        // Add custom data to Whoops
        $handler->addDataTable('InvoicePlane', [
            'Environment' => ENVIRONMENT,
            'Debug Mode' => IP_DEBUG ? 'Yes' : 'No',
            'Request Method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'Request URI' => $_SERVER['REQUEST_URI'] ?? 'Unknown',
            'HTTP Host' => $_SERVER['HTTP_HOST'] ?? 'Unknown',
        ]);

        $whoops->pushHandler($handler);
        $whoops->handleException($exception);
        exit(1);
    }

    /**
     * Render development error page without Whoops
     *
     * @param Throwable $exception
     */
    protected function renderDevelopmentError($exception)
    {
        http_response_code($this->getStatusCode($exception));
        
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error - InvoicePlane</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: #e74c3c;
            color: white;
            padding: 20px;
        }
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
        }
        .header p {
            opacity: 0.9;
        }
        .content {
            padding: 20px;
        }
        .error-type {
            background: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 14px;
        }
        .error-message {
            color: #e74c3c;
            font-size: 18px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        .file-info {
            background: #ecf0f1;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: monospace;
            font-size: 13px;
        }
        .stack-trace {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            font-family: monospace;
            font-size: 12px;
            line-height: 1.6;
        }
        .stack-trace pre {
            margin: 0;
            white-space: pre-wrap;
        }
        .env-badge {
            display: inline-block;
            background: #f39c12;
            color: white;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-left: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>⚠️ An Error Occurred <span class="env-badge">DEVELOPMENT MODE</span></h1>
            <p>InvoicePlane Error Handler</p>
        </div>
        <div class="content">
            <div class="error-type">
                ' . htmlspecialchars(get_class($exception)) . '
            </div>
            <div class="error-message">
                ' . htmlspecialchars($exception->getMessage()) . '
            </div>
            <div class="file-info">
                <strong>File:</strong> ' . htmlspecialchars($exception->getFile()) . '<br>
                <strong>Line:</strong> ' . $exception->getLine() . '
            </div>
            <h3 style="margin-bottom: 10px;">Stack Trace</h3>
            <div class="stack-trace">
                <pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>
            </div>
        </div>
    </div>
</body>
</html>';
        exit(1);
    }

    /**
     * Render user-friendly error page in production
     *
     * @param Throwable $exception
     */
    protected function renderProductionError($exception)
    {
        // Determine HTTP status code
        $statusCode = $this->getStatusCode($exception);
        
        // Set HTTP response code
        http_response_code($statusCode);
        
        // Load error view
        $heading = $this->getErrorHeading($statusCode);
        $message = $this->getErrorMessage($statusCode, $exception);
        
        // Try to load custom error view, fallback to basic HTML
        if (file_exists(APPPATH . 'errors/error_exception.php')) {
            include APPPATH . 'errors/error_exception.php';
        } else {
            $this->renderBasicError($heading, $message, $statusCode);
        }
        
        exit(1);
    }

    /**
     * Fallback error handler when the main handler fails
     *
     * @param Throwable $originalException
     * @param Throwable $handlerException
     */
    protected function renderFallbackError($originalException, $handlerException)
    {
        http_response_code(500);
        
        echo '<!DOCTYPE html><html><head><title>Critical Error</title></head><body>';
        echo '<h1>A critical error occurred</h1>';
        echo '<p>The application encountered an error and the error handler also failed.</p>';
        
        if (ENVIRONMENT === 'development' || IP_DEBUG) {
            echo '<h2>Original Error:</h2>';
            echo '<pre>' . htmlspecialchars($originalException->getMessage()) . '</pre>';
            echo '<h2>Handler Error:</h2>';
            echo '<pre>' . htmlspecialchars($handlerException->getMessage()) . '</pre>';
        } else {
            echo '<p>Please contact the administrator if this problem persists.</p>';
        }
        
        echo '</body></html>';
        exit(1);
    }

    /**
     * Render a basic error page
     *
     * @param string $heading
     * @param string $message
     * @param int $statusCode
     */
    protected function renderBasicError($heading, $message, $statusCode)
    {
        ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($heading); ?> - InvoicePlane</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .error-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
            text-align: center;
        }
        .error-code {
            font-size: 72px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 20px;
        }
        h1 {
            color: #333;
            font-size: 24px;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?php echo $statusCode; ?></div>
        <h1><?php echo htmlspecialchars($heading); ?></h1>
        <p><?php echo htmlspecialchars($message); ?></p>
        <a href="<?php echo base_url(); ?>" class="btn">Return to Home</a>
    </div>
</body>
</html>
        <?php
    }

    /**
     * Log exception to file
     *
     * @param Throwable $exception
     */
    protected function logException($exception)
    {
        $logFile = LOGS_FOLDER . 'exceptions-' . date('Y-m-d') . '.php';
        
        // Create log entry
        $logEntry = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n\n",
            date('Y-m-d H:i:s'),
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        );
        
        // Prepend PHP tag to prevent direct access if first write
        if (!file_exists($logFile)) {
            $logEntry = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n" . $logEntry;
        }
        
        // Write to log file
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get HTTP status code from exception
     *
     * @param Throwable $exception
     * @return int
     */
    protected function getStatusCode($exception)
    {
        // Check if exception has a status code
        if (method_exists($exception, 'getStatusCode')) {
            return $exception->getStatusCode();
        }
        
        // Map exception types to status codes
        $exceptionClass = get_class($exception);
        
        if (strpos($exceptionClass, 'NotFound') !== false || strpos($exceptionClass, '404') !== false) {
            return 404;
        }
        
        if (strpos($exceptionClass, 'Unauthorized') !== false || strpos($exceptionClass, '401') !== false) {
            return 401;
        }
        
        if (strpos($exceptionClass, 'Forbidden') !== false || strpos($exceptionClass, '403') !== false) {
            return 403;
        }
        
        // Default to 500 Internal Server Error
        return 500;
    }

    /**
     * Get error heading based on status code
     *
     * @param int $statusCode
     * @return string
     */
    protected function getErrorHeading($statusCode)
    {
        $headings = [
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Page Not Found',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
        ];
        
        return $headings[$statusCode] ?? 'An Error Occurred';
    }

    /**
     * Get error message based on status code
     *
     * @param int $statusCode
     * @param Throwable $exception
     * @return string
     */
    protected function getErrorMessage($statusCode, $exception)
    {
        $messages = [
            400 => 'The request could not be understood by the server.',
            401 => 'You are not authorized to access this resource.',
            403 => 'You do not have permission to access this resource.',
            404 => 'The page you are looking for could not be found.',
            500 => 'The server encountered an internal error.',
            503 => 'The service is temporarily unavailable.',
        ];
        
        $defaultMessage = $messages[$statusCode] ?? 'An unexpected error occurred.';
        
        // In development mode, show the actual exception message
        if (ENVIRONMENT === 'development' || IP_DEBUG) {
            return $exception->getMessage() ?: $defaultMessage;
        }
        
        return $defaultMessage;
    }

    /**
     * Cleanup resources in finally block
     */
    protected function cleanup()
    {
        // Close database connections if available
        if (function_exists('get_instance')) {
            $CI = &get_instance();
            if (isset($CI->db) && is_object($CI->db)) {
                $CI->db->close();
            }
        }
        
        // Flush any output buffers
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
