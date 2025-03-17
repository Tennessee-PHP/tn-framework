<?php

namespace TN\TN_Core\Component\User\UserProfile\RolesTab;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\Role\RoleGroup;
use TN\TN_Core\Model\User\User;

class SaveRoles extends JSON {
    public function prepare(): void
    {
        $roles = Role::getInstances();
        $id = $_POST['id'] ?? '';
        $user = User::readFromId($id);

        $newRoleKeys = [];

        foreach ($roles as $role) {
            if ($role instanceof RoleGroup) {
                continue;
            }
            if (isset($_POST[$role->key]) && (int)$_POST[$role->key] === 1) {
                $newRoleKeys[] = $role->key;
            }
        }

        $currentRoleKeys = [];
        foreach ($user->getRoles() as $role) {
            if ($role instanceof RoleGroup) {
                continue;
            }
            $currentRoleKeys[] = $role->key;
        }

        foreach (array_diff($newRoleKeys, $currentRoleKeys) as $roleKey) {
            // so this is everything in newRoles that they DON'T already have
            $user->addRole($roleKey);
        }

        foreach (array_diff($currentRoleKeys, $newRoleKeys) as $roleKey) {
            // so this is everything in currentRoles they NO LONGER have
            $user->removeRole($roleKey);
        }

        $this->data = [
            'result' => 'success',
            'message' => 'Roles saved successfully'
        ];
    }
}