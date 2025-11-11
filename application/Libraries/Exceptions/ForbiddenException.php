<?php

namespace App\Libraries\Exceptions;

/**
 * Forbidden Exception (403)
 */
class ForbiddenException extends HttpException
{
    /**
     * Constructor
     *
     * @param string $message
     * @param array $headers
     * @param \Exception|null $previous
     */
    public function __construct($message = 'You do not have permission to access this resource.', array $headers = [], \Exception $previous = null)
    {
        parent::__construct($message, 403, $headers, $previous);
    }
}
