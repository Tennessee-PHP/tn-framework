<?php

namespace TN\TN_Core\Attribute\Route\Access\Restrictions;

use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserApiKey;
use TN\TN_Core\Model\User\UserToken;
use TN\TN_Core\Error\ForbiddenReason;

#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class RoleOnly extends Restriction
{
    public function __construct(protected string $role = '')
    {

    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getAccess(User $user): int
    {
        if (!$user->hasRole($this->role)) {
            $userId = $user->id ?? 0;
            $loggedIn = $user->loggedIn ?? false;
            ForbiddenReason::set([
                'source' => 'RoleOnly',
                'role' => $this->role,
                'userId' => $userId,
                'loggedIn' => $loggedIn,
            ]);
            return self::FORBIDDEN;
        }
        $roleInstance = Role::getInstanceByKey($this->role);
        if ($roleInstance === false || $roleInstance === null || !$roleInstance->getRequiresTwoFactor()) {
            return self::UNRESTRICTED;
        }
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
}