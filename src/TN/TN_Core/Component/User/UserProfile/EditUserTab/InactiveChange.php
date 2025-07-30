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
    public int $userId;
    #[FromPost] public string $comment = '';
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
        $this->observerIsSuperUser = $this->observer->hasRole('super-user');
        if ($this->observerIsSuperUser) {
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->userId)));
        } else {
            $this->user = $this->observer;
        }

        UserInactiveChange::createAndSave($this->user, $this->observer, $this->user->inactive, $this->comment);
        $this->data = [
            'result' => 'success',
            'message' => 'User active status changed'
        ];
    }
}
