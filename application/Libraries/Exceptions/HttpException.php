<?php

namespace App\Libraries\Exceptions;

use Exception;

/**
 * HTTP Exception Base Class
 *
 * Base exception for all HTTP-related exceptions
 */
abstract class HttpException extends Exception
{
    /**
     * @var int HTTP status code
     */
    protected $statusCode;

    /**
     * @var array HTTP headers
     */
    protected $headers = [];

    /**
     * Constructor
     *
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $headers Additional HTTP headers
     * @param Exception|null $previous Previous exception
     */
    public function __construct($message = '', $statusCode = 500, array $headers = [], Exception $previous = null)
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get HTTP headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}
