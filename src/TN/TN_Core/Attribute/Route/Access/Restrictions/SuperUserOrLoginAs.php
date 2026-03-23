<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Error\ForbiddenReason;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserToken;

/**
 * Like RoleOnly('super-user'), but also allows login-as sessions where the active user is the impersonated
 * account and the request auth token (e.g. TN_token) belongs to a super-user.
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class SuperUserOrLoginAs extends Restriction
{
    public function getAccess(User $user): int
    {
        if (!$user->canAccessSuperUserSessionRoutes()) {
            $userId = $user->id ?? 0;
            $loggedIn = $user->loggedIn ?? false;
            ForbiddenReason::set([
                'source' => 'SuperUserOrLoginAs',
                'role' => 'super-user',
                'userId' => $userId,
                'loggedIn' => $loggedIn,
            ]);
            return self::FORBIDDEN;
        }
        $roleInstance = Role::getInstanceByKey('super-user');
        if ($roleInstance === false || $roleInstance === null || !$roleInstance->getRequiresTwoFactor()) {
            return self::UNRESTRICTED;
        }
        if (($_ENV['ENV'] ?? '') === 'development') {
            return self::UNRESTRICTED;
        }
        $request = HTTPRequest::get();
        $token = $request->getAuthToken();
        $userToken = $token !== null && $token !== '' ? UserToken::findValidByToken($token) : null;
        if ($userToken === null || !$userToken->isTwoFactorVerified()) {
            return self::TWO_FACTOR_REQUIRED;
        }
        return self::UNRESTRICTED;
    }
}
