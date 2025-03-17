<?php

namespace TN\TN_Core\Error\Login;

class LoginException extends \TN\TN_Core\Error\TNException
{

    public int $httpResponseCode = 400;
    public bool $messageIsUserFacing = true;
    public function __construct(LoginErrorMessage $message, ?int $attemptsLeft = 0)
    {
        $message = $message->value;
        if ($attemptsLeft > 0) {
            $message .= " ({$attemptsLeft} attempts left)";
        }
        parent::__construct($message);
    }
}