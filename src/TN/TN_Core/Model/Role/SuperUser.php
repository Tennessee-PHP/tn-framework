<?php

namespace TN\TN_Core\Model\Role;

/**
 * a role to allow the editing of users
 */
class SuperUser extends Role
{
    public string $key = 'super-user';
    public string $readable = 'Super Users';
    public string $description = 'Can assign a user role to any user.';
    public bool $requiresTwoFactor = true;
}