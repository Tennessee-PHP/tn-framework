<?php

namespace TN\TN_Core\Service;

use ReflectionMethod;
use TN\TN_Core\Attribute\Route\Access\Restriction;
use TN\TN_Core\Attribute\Route\Access\Restrictions\RoleOnly;
use TN\TN_Core\Error\Access\AccessCsrfInvalidException;
use TN\TN_Core\Error\ForbiddenReason;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Role\Role;
use TN\TN_Core\Model\User\UserToken;

/**
 * CSRF validation for staff mutations. Staff = routes restricted by a role with requiresTwoFactor.
 * CSRF is required only when the auth token was sent via cookie (browser sends it automatically; cross-site risk).
 * When the token was sent in Authorization header, body, or query, CSRF is skipped (no cross-site vector).
 */
class CsrfService
{
    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Whether the current request is a staff mutation (mutating method + route has a role requiring 2FA).
     */
    public static function isStaffMutation(HTTPRequest $request, ReflectionMethod $method): bool
    {
        if (!in_array($request->method, self::MUTATION_METHODS, true)) {
            return false;
        }
        foreach ($method->getAttributes(Restriction::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
            $restriction = $attribute->newInstance();
            if ($restriction instanceof RoleOnly) {
                $role = Role::getInstanceByKey($restriction->getRole());
                if ($role !== false && $role !== null && $role->getRequiresTwoFactor()) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Validate CSRF for the current request. Throws AccessCsrfInvalidException if staff mutation and token missing/invalid.
     * No-op if no token or token not 2FA-verified (2FA gate will block access).
     */
    public static function validateCsrfForRequest(HTTPRequest $request): void
    {
        $token = $request->getAuthToken();
        if ($token === null || $token === '') {
            ForbiddenReason::set(['source' => 'CsrfService', 'reason' => 'no_token']);
            throw new AccessCsrfInvalidException();
        }
        $userToken = UserToken::findValidByToken($token);
        if ($userToken === null || !$userToken->isTwoFactorVerified()) {
            return;
        }
        if ($request->getAuthTokenSource() !== 'cookie') {
            return;
        }
        $provided = $request->getCsrfToken();
        if ($provided === null || $provided === '') {
            ForbiddenReason::set(['source' => 'CsrfService', 'reason' => 'csrf_missing']);
            throw new AccessCsrfInvalidException();
        }
        if ($userToken->csrfSecret === null || !hash_equals($userToken->csrfSecret, $provided)) {
            ForbiddenReason::set(['source' => 'CsrfService', 'reason' => 'csrf_invalid']);
            throw new AccessCsrfInvalidException();
        }
    }

    /**
     * Return the CSRF secret for the current request's token when it is 2FA-verified. Otherwise null.
     * Use when rendering staff pages (e.g. meta tag or session-info endpoint).
     */
    public static function getCsrfSecretForRequest(HTTPRequest $request): ?string
    {
        $token = $request->getAuthToken();
        if ($token === null || $token === '') {
            return null;
        }
        $userToken = UserToken::findValidByToken($token);
        if ($userToken === null || !$userToken->isTwoFactorVerified() || $userToken->csrfSecret === null || $userToken->csrfSecret === '') {
            return null;
        }
        return $userToken->csrfSecret;
    }
}
