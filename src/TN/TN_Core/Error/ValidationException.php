<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Error\TNException;

/**
 * Throwing of 1 or more errors with validating input to a process
 *
 * often when validating inputs, more than one error with the validation might need to be reported to the user.
 * This class allows for validation of an entire input, and then reporting of multiple issues with that validation.
 * It can be passed through to templates where the errors property can be iterated over. Each should be a string.
 *
 */
class ValidationException extends TNException
{
    use \TN\TN_Core\Trait\Getter;

    /** @var string[]|string the error strings */
    protected array $errors;

    /** @var int how many errors are present */
    protected int $numErrors = 0;


    public int $httpResponseCode = 400;
    public bool $messageIsUserFacing = true;

    /** @param string|array $error constructor  */
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
