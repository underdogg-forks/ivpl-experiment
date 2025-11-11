<?php

namespace App\Libraries\Exceptions;

/**
 * Not Found Exception (404)
 */
class NotFoundException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param array $headers
     * @param \Exception|null $previous
     */
    public function __construct($message = 'The requested resource was not found.', array $headers = [], \Exception $previous = null)
    {
        parent::__construct($message, 404, $headers, $previous);
    }
}
