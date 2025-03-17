<?php

namespace TN\TN_Core\Model\Role;


/**
 * Wrapper for individual roles. Allows for more granular control over permissions.
 */
abstract class RoleGroup extends Role
{
    /** @var array privately consumed array that contains all children of a roleGroup. Accessed using the getter function, getChildren() */
    protected array $children = [];


    /** @var array publicly accessible array that keeps tracks of the roles tied to an instance of TN/Users Consumed on the trait RoleOwner.php */
    public array $roles = [];

    public bool $isRoleGroup = true;

    /** @return array */
    public function getChildren(bool $deep = true): array
    {
        $roles = Role::getInstances();
        foreach($roles as $role) {
            // check if we have groupKey
            if (!is_null($role->roleGroup)) {

                // check if groupKey equals our current key
                if ($role->roleGroup === $this->key) {

                    // if so, add it to children
                    $this->children[$role->key] = $role;

                    // if our role is also a group, then run this again
                    if($role instanceof RoleGroup && $deep) {
                        $roleChildren = $role->getChildren();
                        $this->children = array_merge($this->children, $roleChildren);
                    }
                }
            }
        }
        return $this->children;
    }
}