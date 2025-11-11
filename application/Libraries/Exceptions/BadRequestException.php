<?php

namespace App\Libraries\Exceptions;

/**
 * Bad Request Exception (400)
 */
class BadRequestException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param array $headers
     * @param \Exception|null $previous
     */
    public function __construct($message = 'The request could not be understood by the server.', array $headers = [], \Exception $previous = null)
    {
        parent::__construct($message, 400, $headers, $previous);
    }
}
