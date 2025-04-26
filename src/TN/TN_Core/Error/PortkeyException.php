<?php

namespace TN\TN_Core\Error;

/**
 * Exception class for Portkey API-related errors
 */
class PortkeyException extends APIException
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
