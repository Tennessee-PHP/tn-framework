<?php

namespace TN\TN_Core\Error\Access;

/**
 * Authenticated credential was present but is not usable (e.g. expired API key).
 * HTTP 401 with a user-facing message for API clients.
 */
class AccessUnauthorizedException extends AccessException
{
    public int $httpResponseCode = 401;

    use \TN\TN_Core\Trait\Getter;
}
