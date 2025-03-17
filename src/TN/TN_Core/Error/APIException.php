<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Error\TNException;

/**
 * Throwing of 1 or more errors with a remote API
 */
class APIException extends TNException
{
    use \TN\TN_Core\Trait\Getter;

    public int $httpResponseCode = 500;
    public bool $messageIsUserFacing = false;

    /**
     * the error strings
     * @var string[]|string
     */
    protected array $errors;

    /**
     * how many errors are present
     * @var int
     */
    protected int $numErrors = 0;

    /**
     * constructor
     * @param string|array $error
     */
    public function __construct(string|array $error = "")
    {
        parent::__construct();
        if (is_array($error)) {
            $this->errors = $error;
            $this->message = implode('; ', $error);
        } else {
            $this->errors = [$error];
            $this->message = $error;
        }
        $this->numErrors = count($this->errors);
    }
}
