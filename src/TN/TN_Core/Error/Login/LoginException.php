<?php

namespace TN\TN_Core\Error\Login;

class LoginException extends \TN\TN_Core\Error\TNException
{

    public int $httpResponseCode = 400;
    public bool $messageIsUserFacing = true;

    /**
     * @param string|null $customMessage When set, used instead of the enum value (e.g. suspension end date).
     */
    public function __construct(LoginErrorMessage $message, ?int $attemptsLeft = 0, ?string $customMessage = null)
    {
        $text = ($customMessage !== null && $customMessage !== '') ? $customMessage : $message->value;
        if ($attemptsLeft > 0) {
            $text .= " ({$attemptsLeft} attempts left)";
        }
        parent::__construct($text);
    }
}