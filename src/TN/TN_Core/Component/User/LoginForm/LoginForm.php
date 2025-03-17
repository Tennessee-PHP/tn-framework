<?php

namespace TN\TN_Core\Component\User\LoginForm;

use Exception;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\HTMLComponent\Reloadable;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Error\Login\LoginException;
use TN\TN_Core\Error\Login\ResetPasswordTimeoutException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\PasswordReset;
use TN\TN_Core\Model\User\PasswordResetType;
use TN\TN_Core\Model\User\User;

#[Page('Login')]
#[Route('TN_Core:Users:login')]
#[Reloadable]
class LoginForm extends HTMLComponent
{
    public string $login;
    public string $password;
    public ?string $error = null;
    public bool $success;
    public string $action = 'login';

    public function prepare(): void
    {
        $request = HTTPRequest::get();
        $this->login = (string)$request->getPost('login', '');
        $this->password = (string)$request->getPost('password', '');
        $this->action = (string)$request->getPost('action', 'login');

        switch ($this->action) {
            case 'login':
                if (!empty($this->login) && !empty($this->password)) {
                    $this->loginAttempt();
                }
                break;
            case 'reset-password':
                if (!empty($this->login)) {
                    $this->resetPasswordRequest();
                }
                break;
        }
    }

    protected function loginAttempt(): void
    {
        try {
            if ((Stack::resolveClassName(User::class))::attemptLogin($this->login, $this->password)) {
                $this->success = true;
            }
        } catch (LoginException $e) {
            $this->error = $e->getMessage();
        } catch (Exception $e) {
            $this->error = 'An unknown error occurred (' . $e->getMessage() . ')';
        }
    }

    protected function resetPasswordRequest(): void
    {
        try {
            $passwordResetClassName = (Stack::resolveClassName(PasswordReset::class));
            $passwordResetClassName::checkPasswordResetAllowed();
            $user = (Stack::resolveClassName(User::class))::getFromLogin($this->login);
            if (!$user) {
                // this is a strange one, but if there wasn't a user we don't actually want to reveal this!
                // we want to just show the exact same message as success
                // otherwise people could nefariously see if certain email addresses were in our database
                $this->success = true;
                return;
            }
            $passwordResetClassName::startFromUser($this, PasswordResetType::Reset);
            $this->success = true;
        } catch (ResetPasswordTimeoutException) {
            $this->error = 'Too many password reset attempts.';
        } catch (Exception $e) {
            $this->error = 'An unknown error occurred (' . $e->getMessage() . ')';
        }

    }
}