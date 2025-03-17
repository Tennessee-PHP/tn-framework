<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Error\TNException;

/**
 * Throwing of 1 or more errors with how the code is written
 *
 */
class CodeException extends TNException
{
    use \TN\TN_Core\Trait\Getter;

    public int $httpResponseCode = 500;
    public bool $messageIsUserFacing = false;

    /** @var string[]|string the error strings */
    protected array $errors;

    /** @var int how many errors are present */
    protected int $numErrors = 0;

    /** @param string|array $error constructor */
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

?>