<?php

namespace TN\TN_Core\Error;

/**
 * Request exceeded the configured rate limit (e.g. staff routes by IP or token).
 * HTTP 429 Too Many Requests.
 */
class RateLimitExceededException extends TNException
{
    public int $httpResponseCode = 429;

    /** @var int seconds after which the client may retry */
    public int $retryAfter = 60;

    public function __construct(string $message = 'Rate limit exceeded', int $retryAfter = 60, ?\Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->retryAfter = $retryAfter;
    }
}
