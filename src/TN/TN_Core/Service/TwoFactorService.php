<?php

namespace TN\TN_Core\Service;

use PragmaRX\Google2FA\Google2FA;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserToken;

/**
 * TOTP two-factor authentication: verify codes, enrol users, build otpauth URIs.
 * Issuer is read from ENV (e.g. TOTP_ISSUER); no app name in the framework.
 */
class TwoFactorService
{
    private static ?Google2FA $google2fa = null;

    private static function getGoogle2FA(): Google2FA
    {
        if (self::$google2fa === null) {
            self::$google2fa = new Google2FA();
        }
        return self::$google2fa;
    }

    /**
     * Verify a 6-digit TOTP code for the user. Returns false if user has no secret or code is invalid.
     */
    public static function verifyCode(User $user, string $code): bool
    {
        $secret = $user->totpSecret ?? '';
        if ($secret === '') {
            return false;
        }
        return self::verifyCodeForSecret($secret, $code);
    }

    /**
     * Verify a 6-digit TOTP code against a secret (e.g. during setup before persisting).
     */
    public static function verifyCodeForSecret(string $secret, string $code): bool
    {
        if ($secret === '') {
            return false;
        }
        $code = trim($code);
        if (strlen($code) !== 6 || !ctype_digit($code)) {
            return false;
        }
        $result = self::getGoogle2FA()->verifyKey($secret, $code, 1);
        return $result !== false;
    }

    /**
     * Verify the code and, if valid, set twoFaVerifiedAt, expiresTs, and csrfSecret (if not already set) on the token.
     */
    public static function verifyAndUpgradeToken(User $user, UserToken $userToken, string $code): bool
    {
        if (!self::verifyCode($user, $code)) {
            return false;
        }
        $now = Time::getNow();
        $update = [
            'twoFaVerifiedAt' => $now,
            'expiresTs' => min($userToken->expiresTs, $now + Time::ONE_MONTH),
        ];
        if ($userToken->csrfSecret === null || $userToken->csrfSecret === '') {
            $update['csrfSecret'] = bin2hex(random_bytes(32));
        }
        $userToken->update($update);
        if (isset($update['csrfSecret'])) {
            $userToken->csrfSecret = $update['csrfSecret'];
        }
        return true;
    }

    /**
     * Generate a new TOTP secret (base32).
     */
    public static function generateSecret(): string
    {
        return self::getGoogle2FA()->generateSecretKey();
    }

    /**
     * Build otpauth URI for QR and manual entry. Issuer from ENV (TOTP_ISSUER or SITE_NAME).
     * Account label shown in authenticator app: "{issuer}: {username}".
     */
    public static function getOtpAuthUri(User $user, string $secret, ?string $label = null): string
    {
        $issuer = $_ENV['TOTP_ISSUER'] ?? $_ENV['SITE_NAME'] ?? '';
        $holder = $label ?? $user->username ?? $user->email ?? (string)$user->id;
        return self::getGoogle2FA()->getQRCodeUrl($issuer, $holder, $secret);
    }

    /**
     * Generate secret and otpauth URI without saving. Use for setup flow; persist after verifyCodeForSecret.
     */
    public static function prepareEnrolment(User $user): array
    {
        $secret = self::generateSecret();
        $otpauthUri = self::getOtpAuthUri($user, $secret);
        return [
            'secret' => $secret,
            'otpauthUri' => $otpauthUri,
        ];
    }

    /**
     * Generate secret, save to user, return data for the app to display (QR + manual key).
     */
    public static function enrolUser(User $user): array
    {
        $data = self::prepareEnrolment($user);
        $user->update(['totpSecret' => $data['secret']]);
        return $data;
    }

    /** TTL in seconds for setup token (file-based, for Bearer-only / cross-origin clients that don't send session). */
    private const SETUP_TOKEN_TTL = 600;

    /**
     * Store 2FA setup secret by one-time token (for SPA/Bearer clients that don't send session cookie).
     * Returns the token to return to the client; client sends it back on POST to confirm.
     */
    public static function storeSetupPayload(int $userId, string $secret): string
    {
        $token = bin2hex(random_bytes(16));
        $dir = rtrim($_ENV['TN_TMP_ROOT'] ?? '', '/') . '/2fa_setup';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $path = $dir . '/' . $token;
        $payload = ['userId' => $userId, 'secret' => $secret, 'createdAt' => time()];
        file_put_contents($path, json_encode($payload), LOCK_EX);
        return $token;
    }

    /**
     * Retrieve and consume 2FA setup payload by token. Returns null if missing or expired.
     */
    public static function consumeSetupPayload(string $token): ?array
    {
        if ($token === '' || preg_match('/[^a-f0-9]/', $token)) {
            return null;
        }
        $dir = rtrim($_ENV['TN_TMP_ROOT'] ?? '', '/') . '/2fa_setup';
        $path = $dir . '/' . $token;
        if (!is_file($path)) {
            return null;
        }
        $age = time() - filemtime($path);
        if ($age > self::SETUP_TOKEN_TTL) {
            @unlink($path);
            return null;
        }
        $raw = @file_get_contents($path);
        @unlink($path);
        if ($raw === false) {
            return null;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['userId'], $payload['secret'])) {
            return null;
        }
        return ['userId' => (int)$payload['userId'], 'secret' => (string)$payload['secret']];
    }
}
