<?php

namespace TN\TN_Core\Component\User\UserProfile\EditUserTab;

use TN\TN_Core\Attribute\Components\FromPost;
use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ResourceNotFoundException;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;

class Merge extends JSON
{
    public int $userId;
    public ?User $user;
    public User $observer;
    public bool $observerIsSuperUser;


    public function prepare(): void
    {
        $this->observer = User::getActive();
        $this->observerIsSuperUser = $this->observer->hasRole('super-user');
        if ($this->observerIsSuperUser) {
            $this->user = User::searchOne(new SearchArguments(conditions: new SearchComparison('`id`', '=', $this->userId)));
        } else {
            $this->user = $this->observer;
        }

        $secondaryUser = User::readFromId($_POST['secondaryUserId']);
        if ($this->user->id === $secondaryUser->id) {
            throw new ValidationException('Cannot merge with self');
        }

        if ($secondaryUser->inactive) {
            throw new ValidationException('Cannot merge with inactive user');
        }

        if ($secondaryUser->hasRole('super-user')) {
            throw new ValidationException('Cannot merge with super user');
        }

        $this->user->mergeWithUser($secondaryUser, $this->observer);

        $this->data = [
            'result' => 'success',
            'message' => 'User merged'
        ];
    }
}
