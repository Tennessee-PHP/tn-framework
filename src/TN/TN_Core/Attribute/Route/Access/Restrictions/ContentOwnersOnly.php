<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;

/**
 * Allows a route to be restricted according to whether the logged in user has access to certain content, through an
 * associated subscription to that content's level
 *
 * @see \TN\TN_Billing\Model\Subscription\Subscription
 * @see \TN\Model\Subscription\Content
 * @see \TN\TN_Billing\Model\Subscription\Plan\Plan
 *
 */
#[\Attribute(\Attribute::TARGET_METHOD)]
class ContentOwnersOnly extends Restriction
{
    public string $content;
    public function __construct(string $content)
    {
        $this->content = $content;
    }

    public function getAccess(User $user): int
    {
        return $user->canViewContent($this->content) ? self::UNRESTRICTED : self::ROADBLOCKED;
    }
}