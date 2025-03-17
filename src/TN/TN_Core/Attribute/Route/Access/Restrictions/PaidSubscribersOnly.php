<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\User\User;

#[\Attribute(\Attribute::TARGET_METHOD)]
class PaidSubscribersOnly extends Restriction
{
    public function getAccess(User $user): int
    {
        return (bool)$user->isPaidSubscriber() ? self::UNRESTRICTED : self::UNMATCHED;
    }
}