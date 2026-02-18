<?php

namespace TN\TN_Core\Error\Access;

/**
 * Request is a staff mutation but CSRF token is missing or invalid.
 */
class AccessCsrfInvalidException extends AccessException
{
    use \TN\TN_Core\Trait\Getter;
}
