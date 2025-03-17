<?php

namespace TN\TN_Core\Error\Access;

use TN\TN_Core\Error\Access\AccessException;

/**
 * user must log in to attempt to access the resource
 */
class AccessLoginRequiredException extends AccessException
{
    use \TN\TN_Core\Trait\Getter;
}
