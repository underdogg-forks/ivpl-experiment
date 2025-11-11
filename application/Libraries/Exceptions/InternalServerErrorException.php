<?php

namespace App\Libraries\Exceptions;

/**
 * Internal Server Error Exception (500)
 */
class InternalServerErrorException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param array $headers
     * @param \Exception|null $previous
     */
    public function __construct($message = 'The server encountered an internal error.', array $headers = [], \Exception $previous = null)
    {
        parent::__construct($message, 500, $headers, $previous);
    }
}
