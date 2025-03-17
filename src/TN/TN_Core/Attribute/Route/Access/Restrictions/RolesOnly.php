<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Model\User\Staffer;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\User\User;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RolesOnly extends Restriction
{
    public function __construct(protected array $roles = [])
    {

    }

    public function getAccess(User $user): int
    {
        foreach ($this->roles as $role) {
            if ($user->hasRole($role)) {
                return self::UNRESTRICTED;
            }
        }
        return self::FORBIDDEN;
    }
}