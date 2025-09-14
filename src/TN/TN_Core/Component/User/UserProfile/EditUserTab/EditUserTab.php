<?php

namespace TN\TN_Core\Component\User\UserProfile\EditUserTab;

use TN\TN_Core\Component\User\UserProfile\UserProfileTab;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserInactiveChange;

class EditUserTab extends UserProfileTab
{
    public static string $tabKey = 'user-info';
    public static string $tabReadable = 'Profile';
    public static int $sortOrder = 1;

    public ?User $observer;
    public bool $observerIsSuperUser;
    public array $userInactiveChanges;

    public function prepare(): void
    {
        $this->observer = User::getActive();
        $this->observerIsSuperUser = $this->observer->hasRole('super-user') || $this->observer->hasRole('user-admin');
        $this->userInactiveChanges = UserInactiveChange::getUserChanges($this->user);
    }
}
