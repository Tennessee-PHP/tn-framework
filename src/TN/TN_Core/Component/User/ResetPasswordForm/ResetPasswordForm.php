<?php

namespace TN\TN_Core\Component\User\ResetPasswordForm;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Attribute\Components\FromRequest;
use TN\TN_Core\Attribute\Components\HTMLComponent\Page;
use TN\TN_Core\Attribute\Components\Route;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\PasswordReset;

#[Page('Reset Password', '', false)]
#[Route('TN_Core:User:ResetPasswordForm')]
class ResetPasswordForm extends HTMLComponent
{
    #[FromRequest] public string $key;
    public ?PasswordReset $passwordReset;
    public bool $expired = false;
    public bool $success = false;
    #[FromPost] public ?string $password = null;
    #[FromPost] public ?string $passwordRepeat = null;
    public ?string $errorMessage = null;

    public function prepare(): void
    {
        if (!isset($this->key) || empty($this->key)) {
            throw new ResourceNotFoundException('Password reset key');
        }

        $this->passwordReset = PasswordReset::getFromKey($this->key);
        if (!$this->passwordReset) {
            throw new ResourceNotFoundException('Password reset request');
        }

        if ($this->passwordReset->isExpired()) {
            $this->expired = true;
        }

        if ($this->password) {
            $user = $this->passwordReset->getUser();
            try {
                $user->update([
                    'password' => $this->password ?? '',
                    'passwordRepeat' => $this->passwordRepeat ?? ''
                ], true);
            } catch (ValidationException $e) {
                $this->errorMessage = $e->getMessage();
                return;
            }

            $user->login($this->password);
            $this->passwordReset->complete();
            $this->success = true;
        }
    }
}
