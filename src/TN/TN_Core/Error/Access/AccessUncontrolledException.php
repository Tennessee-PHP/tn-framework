<?php

namespace TN\TN_Core\Error\Access;

use TN\TN_Core\Error\Access\AccessException;

/**
 * user does not have permission to access the requested resource
 */
class AccessUncontrolledException extends AccessException
{
    use \TN\TN_Core\Trait\Getter;
}
