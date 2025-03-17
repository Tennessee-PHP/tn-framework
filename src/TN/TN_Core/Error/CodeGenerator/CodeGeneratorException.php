<?php

namespace TN\TN_Core\Error\CodeGenerator;

class CodeGeneratorException extends \TN\TN_Core\Error\TNException
{
    public int $httpResponseCode = 500;
    public bool $messageIsUserFacing = true;
    public function __construct(CodeGeneratorErrorMessage $message, ?int $attemptsLeft = 0)
    {
        $message = $message->value;
        if ($attemptsLeft > 0) {
            $message .= " ({$attemptsLeft} attempts left)";
        }
        parent::__construct($message);
    }
}