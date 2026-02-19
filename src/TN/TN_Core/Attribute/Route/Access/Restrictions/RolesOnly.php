<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserToken;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RolesOnly extends Restriction
{
    public function __construct(protected array $roles = [])
    {

    }

    public function getAccess(User $user): int
    {
        $hasAllowedRole = false;
        $matchedRoleRequiresTwoFactor = false;
        foreach ($this->roles as $roleKey) {
            if ($user->hasRole($roleKey)) {
                $hasAllowedRole = true;
                $roleInstance = Role::getInstanceByKey($roleKey);
                if ($roleInstance !== false && $roleInstance !== null && $roleInstance->getRequiresTwoFactor()) {
                    $matchedRoleRequiresTwoFactor = true;
                    break;
                }
            }
        }
        if (!$hasAllowedRole) {
            return self::FORBIDDEN;
        }
        if ($matchedRoleRequiresTwoFactor) {
            $request = HTTPRequest::get();
            $token = $request->getAuthToken();
            $userToken = $token !== null && $token !== '' ? UserToken::findValidByToken($token) : null;
            if ($userToken === null || !$userToken->isTwoFactorVerified()) {
                return self::TWO_FACTOR_REQUIRED;
            }
        }
        return self::UNRESTRICTED;
    }
}