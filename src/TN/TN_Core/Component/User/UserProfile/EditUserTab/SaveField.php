<?php

namespace TN\TN_Core\Component\User\UserProfile\EditUserTab;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

class SaveField extends JSON
{
    public int $userId;
    public ?User $user;
    public User $observer;
    public bool $observerIsSuperUser;

    public function prepare(): void
    {
        // Handle "me" resolution
        if ((string)$this->userId === 'me') {
            $this->userId = User::getActive()->id;
        }

        $this->observer = User::getActive();
        $this->observerIsSuperUser = $this->observer->hasRole('super-user') || $this->observer->hasRole('user-admin');
        if ($this->observerIsSuperUser) {
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->userId)));
        } else {
            $this->user = $this->observer;
        }


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

        $passwordChangedForSelf = isset($update['password']) && $this->user->id === $this->observer->id;

        $this->user->update($update);

        $this->data = [
            'result' => 'success',
            'message' => 'User updated'
        ];
        if ($passwordChangedForSelf) {
            $this->data['logoutRequired'] = true;
        }
    }
}
