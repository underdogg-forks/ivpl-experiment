<?php

namespace App\Libraries;

use Exception;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 * HTTP Request Handler with Try/Catch/Finally
 *
 * Provides a wrapper for HTTP requests with comprehensive error handling
 *
 * @author InvoicePlane Developers & Contributors
 * @copyright Copyright (c) 2012 - 2024 InvoicePlane.com
 * @license https://invoiceplane.com/license.txt
 * @link https://invoiceplane.com
 */
class RequestHandler
{
    /**
     * @var array Request logs
     */
    protected $logs = [];

    /**
     * Make an HTTP request with error handling
     *
     * @param string $url The URL to request
     * @param string $method HTTP method (GET, POST, PUT, DELETE, etc.)
     * @param array $data Request data
     * @param array $headers Additional headers
     * @param callable|null $finally Cleanup callback
     * @return array Response data
     * @throws Exception
     */
    public function request($url, $method = 'GET', $data = [], $headers = [], $finally = null)
    {
        $startTime = microtime(true);
        $response = null;
        $error = null;

        try {
            // Log outgoing request
            $this->logOutgoingRequest($url, $method, $data);

            // Make the request using cURL
            $ch = curl_init();

            // Set URL
            if ($method === 'GET' && !empty($data)) {
                $url .= '?' . http_build_query($data);
            }
            curl_setopt($ch, CURLOPT_URL, $url);

            // Set method and data
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
            if (in_array($method, ['POST', 'PUT', 'PATCH']) && !empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                $headers[] = 'Content-Type: application/json';
            }

            // Set headers
            if (!empty($headers)) {
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            }

            // Other options
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

            // Execute request
            $responseBody = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            $curlErrno = curl_errno($ch);

            curl_close($ch);

            // Check for cURL errors
            if ($curlErrno !== 0) {
                throw new Exception("cURL Error: {$curlError}", $curlErrno);
            }

            // Parse response
            $response = [
                'status_code' => $httpCode,
                'body' => $responseBody,
                'data' => json_decode($responseBody, true),
                'success' => $httpCode >= 200 && $httpCode < 300,
            ];

            // Check HTTP status
            if (!$response['success']) {
                throw new Exception("HTTP Error {$httpCode}: " . substr($responseBody, 0, 200));
            }

            return $response;
        } catch (Exception $e) {
            $error = $e;

            // Log the error
            $this->logRequestError($url, $method, $e);

            // Re-throw the exception
            throw $e;
        } finally {
            // Calculate duration
            $duration = microtime(true) - $startTime;

            // Log incoming response
            $this->logIncomingResponse($url, $response, $error, $duration);

            // Execute custom cleanup
            if ($finally !== null && is_callable($finally)) {
                try {
                    $finally($response, $error);
                } catch (Exception $e) {
                    log_message('error', 'Error in finally callback: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Log outgoing request
     *
     * @param string $url
     * @param string $method
     * @param array $data
     */
    protected function logOutgoingRequest($url, $method, $data)
    {
        $log = [
            'timestamp' => microtime(true),
            'direction' => 'outgoing',
            'method' => $method,
            'url' => $url,
            'data_size' => strlen(json_encode($data)),
        ];

        $this->logs[] = $log;

        if (defined('IP_DEBUG') && IP_DEBUG) {
            log_message('debug', sprintf(
                'Outgoing Request: %s %s',
                $method,
                $url
            ));
        }
    }

    /**
     * Log incoming response
     *
     * @param string $url
     * @param array|null $response
     * @param Exception|null $error
     * @param float $duration
     */
    protected function logIncomingResponse($url, $response, $error, $duration)
    {
        $log = [
            'timestamp' => microtime(true),
            'direction' => 'incoming',
            'url' => $url,
            'duration' => $duration,
            'status_code' => $response['status_code'] ?? ($error ? 0 : null),
            'error' => $error ? $error->getMessage() : null,
        ];

        $this->logs[] = $log;

        if (defined('IP_DEBUG') && IP_DEBUG) {
            log_message('debug', sprintf(
                'Incoming Response: %s - Status: %s - Duration: %.4fs',
                $url,
                $log['status_code'] ?? 'Error',
                $duration
            ));
        }
    }

    /**
     * Log request error
     *
     * @param string $url
     * @param string $method
     * @param Exception $exception
     */
    protected function logRequestError($url, $method, $exception)
    {
        $logFile = defined('LOGS_FOLDER') ? LOGS_FOLDER . 'http-errors-' . date('Y-m-d') . '.php' : null;

        if ($logFile) {
            $logEntry = sprintf(
                "[%s] %s %s - Error: %s\n",
                date('Y-m-d H:i:s'),
                $method,
                $url,
                $exception->getMessage()
            );

            if (!file_exists($logFile)) {
                $logEntry = "<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>\n\n" . $logEntry;
            }

            @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Get all request logs
     *
     * @return array
     */
    public function getLogs()
    {
        return $this->logs;
    }

    /**
     * Clear logs
     */
    public function clearLogs()
    {
        $this->logs = [];
    }
}
