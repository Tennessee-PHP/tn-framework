<?php

namespace TN\TN_Core\Attribute\Route\Access;
use TN\TN_Core\Model\User\User;

/**
 * a restriction on route access. A way for sub-classes to set permissions on routes to one of the constants on this
 * class
 * 
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
abstract class Restriction
{
    /**
     * the visitor has unrestricted access to this resource
     */
    const int UNRESTRICTED = 0;

    /**
     * the visitor can access a preview to the resource; the remainder is roadblocked
     */
    const int ROADBLOCKED = 1;

    /**
     * the visitor is prevented from having this resource match to their request (404 results if no other routes match)
     */
    const int UNMATCHED = 2;

    /**
     * the visitor is prevented from accessing this resource (results in 403)
     */
    const int FORBIDDEN = 3;

    /**
     * the user should be redirected to the login before viewing the page
     */
    const int LOGIN_REQUIRED = 4;

    /**
     * the user must complete two-factor verification before accessing this resource
     */
    const int TWO_FACTOR_REQUIRED = 5;

    /**
     * given the current user, return the integer access level
     * @param User $user
     * @return int
     */
    abstract public function getAccess(User $user): int;
}