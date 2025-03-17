<?php

namespace TN\TN_Core\Component\User\UserProfile\EditUserTab;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

class SaveField extends JSON {
    public string $username;
    public ?User $user;
    public User $observer;
    public bool $observerIsSuperUser;

    public function prepare(): void
    {
        if ($this->username === 'me') {
            $this->user = User::getActive();
        } else {
            if (!User::getActive()->hasRole('super-user')) {
                throw new ResourceNotFoundException('Cannot view this user');
            }
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`username`', '=', $this->username)));
        }
        $this->observer = User::getActive();
        $this->observerIsSuperUser = $this->observer->hasRole('super-user');

        $update = [];
        foreach ($_POST as $key => $value) {
            if (in_array($key, ['email', 'first', 'last'])) {
                $update[$key] = $value;
            }

            // username = only if they are super user
            if (in_array($key, ['username'])) {
                if ($this->observerIsSuperUser) {
                    $update[$key] = $value;
                }
            }

            // password = must match the current password also
            if (in_array($key, ['password'])) {
                if ($this->observerIsSuperUser) {
                    $update[$key] = $value;
                    $update['passwordRepeat'] = $_POST['passwordRepeat'];
                } else {
                    if (!$this->user->verifyPassword($_POST['currentPassword'])) {
                        throw new ValidationException('Current password is incorrect');
                    }
                    $update[$key] = $value;
                    $update['passwordRepeat'] = $_POST['passwordRepeat'];
                }
            }
        }

        $this->user->update($update);
        $this->data = [
            'result' => 'success',
            'message' => 'User updated'
        ];
    }
}