<?php

namespace TN\TN_Core\Error\Access;

use TN\TN_Core\Error\Access\AccessException;

/**
 * user must complete two-factor verification to access the resource
 */
class AccessTwoFactorRequiredException extends AccessException
{
    use \TN\TN_Core\Trait\Getter;
}
