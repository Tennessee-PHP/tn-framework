<?php

namespace TN\TN_Core\Error\Access;

use TN\TN_Core\Error\TNException;

/**
 * user does not have permission to access the requested resource
 */
class AccessException extends TNException
{

    public int $httpResponseCode = 403;
    public bool $messageIsUserFacing = true;
    use \TN\TN_Core\Trait\Getter;
}
