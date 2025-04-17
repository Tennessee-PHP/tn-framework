<?php

namespace TN\TN_Core\Error;

use TN\TN_Core\Model\User\User;

class TNException extends \Exception
{
    public int $httpResponseCode = 400;
    public bool $messageIsUserFacing = false;
    protected bool $userIsAdmin;

    protected function getUserIsAdmin(): bool
    {
        return true;
        if (!isset($this->userIsAdmin)) {
            $this->userIsAdmin = User::getActive()->hasRole('super-user') || User::getActive()->isLoggedInAsOther();
        }
        return $this->userIsAdmin;
    }

    public function canShowMessage(): bool
    {
        return $this->messageIsUserFacing || $this->getUserIsAdmin() || $_ENV['ENV'] === 'development';
    }

    public function getDisplayMessage(): string
    {
        if ($this->canShowMessage()) {
            $message = $this->getPrevious() ? $this->getPrevious()->getMessage() : $this->message;
        } else {
            $message = 'An error has occurred - it has been logged! Please try again later.';
        }

        if ($this->getUserIsAdmin()) {
            $message .= PHP_EOL . PHP_EOL;
            $message .= 'Admin-only viewable error (not visible by regular users): ' . PHP_EOL;

            if ($this->getPrevious()) {
                $prev = $this->getPrevious();
                $message .= get_class($prev) . ": [{$prev->getCode()}] {$prev->getMessage()} on line {$prev->getLine()} in file {$prev->getFile()}\n";
                $message .= PHP_EOL . PHP_EOL;
                $message .= 'Stack trace: ' . PHP_EOL;
                $message .= $prev->getTraceAsString();
            } else {
                $message .= __CLASS__ . ": [{$this->code}] {$this->message} on line {$this->line} in file {$this->file}\n";
                $message .= PHP_EOL . PHP_EOL;
                $message .= 'Stack trace: ' . PHP_EOL;
                $message .= $this->getTraceAsString();
            }
        }

        return $message;
    }

    public function __toString(): string
    {
        if ($this->getPrevious()) {
            $prev = $this->getPrevious();
            return get_class($prev) . ": [{$prev->getCode()}] {$prev->getMessage()} on line {$prev->getLine()} in file {$prev->getFile()}\n";
        }
        return __CLASS__ . ": [{$this->code}] {$this->message} on line {$this->line} in file {$this->file}\n";
    }
}
