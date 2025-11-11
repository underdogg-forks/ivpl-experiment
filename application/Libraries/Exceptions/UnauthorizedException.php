<?php

namespace App\Libraries\Exceptions;

/**
 * Unauthorized Exception (401)
 */
class UnauthorizedException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param array $headers
     * @param \Exception|null $previous
     */
    public function __construct($message = 'You are not authorized to access this resource.', array $headers = [], \Exception $previous = null)
    {
        parent::__construct($message, 401, $headers, $previous);
    }
}
