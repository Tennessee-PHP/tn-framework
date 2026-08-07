<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserApiKey;
use TN\TN_Core\Model\User\UserToken;

#[\Attribute(\Attribute::TARGET_METHOD)]
class RolesOnly extends Restriction
{
    public function __construct(protected array $roles = [])
    {

    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return $this->roles;
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
            if (($_ENV['ENV'] ?? '') === 'development') {
                return self::UNRESTRICTED;
            }
            $request = HTTPRequest::get();
            $token = $request->getAuthToken();
            if ($token !== null && $token !== '') {
                $userToken = UserToken::findValidByToken($token);
                if ($userToken !== null && $userToken->isTwoFactorVerified()) {
                    return self::UNRESTRICTED;
                }
                $apiKey = UserApiKey::findValidByRawToken($token);
                if ($apiKey !== null && (int) $apiKey->userId === (int) $user->id) {
                    return self::UNRESTRICTED;
                }
            }
            return self::TWO_FACTOR_REQUIRED;
        }
        return self::UNRESTRICTED;
    }
}