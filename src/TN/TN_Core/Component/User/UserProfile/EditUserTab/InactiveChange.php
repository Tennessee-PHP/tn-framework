<?php

namespace TN\TN_Core\Component\User\UserProfile\EditUserTab;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserInactiveChange;

class InactiveChange extends JSON
{
    public string $username;
    public ?User $user;
    public User $observer;
    public bool $observerIsSuperUser;

    public function prepare(): void
    {
        if ($this->username === 'me') {
            $this->user = User::getActive();
        } else {
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`username`', '=', $this->username)));
        }
        $this->observer = User::getActive();
        $this->observerIsSuperUser = $this->observer->hasRole('super-user');
        UserInactiveChange::createAndSave($this->user, $this->observer, $this->user->inactive, $_POST['comment']);
        $this->data = [
            'result' => 'success',
            'message' => 'User active status changed'
        ];
    }
}
