<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Model\User\Staffer;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\User\User;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RoleOnly extends Restriction
{
    public function __construct(protected string $role = '')
    {

    }

    public function getAccess(User $user): int
    {
        return ($user->hasRole($this->role)) ? self::UNRESTRICTED : self::FORBIDDEN;
    }
}