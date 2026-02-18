<?php

namespace TN\TN_Core\Component\User\TwoFactor\SetupConfirm;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Service\TwoFactorService;

/**
 * POST auth/two-factor/setup: confirm 2FA setup with code and setupToken (from GET response).
 * Works for Bearer-only / cross-origin clients; no session required.
 */
class SetupConfirm extends JSON
{
    public function prepare(): void
    {
        $request = HTTPRequest::get();
        $user = User::getActive();
        if (!$user instanceof User || !$user->loggedIn) {
            throw new ValidationException('Login required');
        }

        $jsonBody = $request->getJSONRequestBody() ?? [];
        $setupToken = isset($jsonBody['setupToken']) ? trim((string)$jsonBody['setupToken']) : '';
        $code = isset($jsonBody['code']) ? trim((string)$jsonBody['code']) : '';

        $payload = TwoFactorService::consumeSetupPayload($setupToken);

        if ($payload === null || $payload['userId'] !== $user->id) {
            throw new ValidationException('Setup expired or not started. Please start setup again.');
        }

        if ($code === '') {
            throw new ValidationException('Code is required');
        }

        if (!TwoFactorService::verifyCodeForSecret($payload['secret'], $code)) {
            throw new ValidationException('Invalid or expired code');
        }

        $user->update(['totpSecret' => $payload['secret']]);

        $this->data = [
            'result' => 'success',
            'message' => 'Two-factor authentication is now enabled',
        ];
    }
}
