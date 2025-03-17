<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Error\TNException;

/**
 * Throwing of 1 or more errors with a remote API
 */
class ResourceNotFoundException extends TNException
{

    public int $httpResponseCode = 404;
    public bool $messageIsUserFacing = true;

    use \TN\TN_Core\Trait\Getter;
    /**
     * constructor
     * @param string $resource
     */
    public function __construct(string $resource = "")
    {
        parent::__construct();
        $this->message = 'Could not find ' . $resource;
    }
}
