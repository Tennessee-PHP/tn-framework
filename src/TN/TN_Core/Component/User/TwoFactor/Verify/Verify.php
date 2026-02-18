<?php

namespace TN\TN_Core\Component\User\TwoFactor\Verify;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserToken;
use TN\TN_Core\Service\TwoFactorService;

/**
 * POST auth/two-factor/verify: submit 6-digit code, upgrade token to 2FA-verified.
 * Returns success; optionally csrfToken for cookie-based clients.
 */
class Verify extends JSON
{
    public function prepare(): void
    {
        $request = HTTPRequest::get();
        $user = User::getActive();
        if (!$user instanceof User || !$user->loggedIn) {
            throw new ValidationException('Login required');
        }

        $tokenString = $request->getAuthToken();
        if ($tokenString === null || $tokenString === '') {
            throw new ValidationException('Token required');
        }

        $userToken = UserToken::findValidByToken($tokenString);
        if ($userToken === null) {
            throw new ValidationException('Invalid or expired token');
        }

        if ($userToken->userId !== $user->id) {
            throw new ValidationException('Token does not match user');
        }

        $jsonBody = $request->getJSONRequestBody();
        $code = isset($jsonBody['code']) ? trim((string)$jsonBody['code']) : '';
        if ($code === '') {
            throw new ValidationException('Code is required');
        }

        // Re-load user from DB with same class as active user so totpSecret is present (avoids cache / wrong model).
        $userClass = $user instanceof User ? get_class($user) : User::class;
        $userForVerify = $userClass::readFromId($user->id, true);
        if (!$userForVerify instanceof User) {
            throw new ValidationException('User not found');
        }

        if (!TwoFactorService::verifyAndUpgradeToken($userForVerify, $userToken, $code)) {
            throw new ValidationException('Invalid or expired code');
        }

        $this->data = [
            'result' => 'success',
            'message' => 'Two-factor verification successful',
        ];

        if ($userToken->csrfSecret !== null && $userToken->csrfSecret !== '') {
            $this->data['csrfToken'] = $userToken->csrfSecret;
        }
    }
}
