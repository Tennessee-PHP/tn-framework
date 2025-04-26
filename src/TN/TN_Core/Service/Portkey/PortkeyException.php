<?php

namespace TN\TN_Core\Service\Portkey;

use TN\TN_Core\Error\TNException;

/**
 * Exception class for Portkey-related errors
 */
class PortkeyException extends TNException
{
    public function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }
}
