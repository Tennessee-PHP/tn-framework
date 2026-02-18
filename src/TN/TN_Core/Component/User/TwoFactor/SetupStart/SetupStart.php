<?php

namespace TN\TN_Core\Component\User\TwoFactor\SetupStart;

use TN\TN_Core\Component\Renderer\JSON\JSON;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Service\TwoFactorService;

/**
 * GET auth/two-factor/setup: start 2FA enrolment; return otpauthUri, secret, and setupToken.
 * Secret is stored by setupToken (file-based) so Bearer-only / cross-origin clients work without session.
 */
class SetupStart extends JSON
{
    public function prepare(): void
    {
        $user = User::getActive();
        if (!$user instanceof User || !$user->loggedIn) {
            throw new ValidationException('Login required');
        }

        if ($user->totpSecret !== null && $user->totpSecret !== '') {
            throw new ValidationException('Two-factor is already set up for this account');
        }

        $data = TwoFactorService::prepareEnrolment($user);
        $setupToken = TwoFactorService::storeSetupPayload($user->id, $data['secret']);

        $this->data = [
            'result' => 'success',
            'otpauthUri' => $data['otpauthUri'],
            'secret' => $data['secret'],
            'setupToken' => $setupToken,
        ];
    }
}
