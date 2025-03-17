<?php

namespace TN\TN_Core\Component\User\UserProfile\RolesTab;

use TN\TN_Core\Component\User\UserProfile\UserProfileTab;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\User;

class RolesTab extends UserProfileTab {
    public static string $tabKey = 'roles';
    public static string $tabReadable = 'Roles';
    public static int $sortOrder = 2;
    public array $roles;
    public User $observer;
    public array $userRoleKeys;

    public function prepare(): void
    {
        $this->roles = [];
        foreach (Role::getInstances() as $role) {
            if (!is_null($role->roleGroup)) {
                continue;
            }
            $this->roles[] = $role;
        }
        $this->observer = User::getActive();
        $this->userRoleKeys = $this->user->getRoleKeys();
    }
}