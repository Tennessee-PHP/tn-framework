<?php

namespace TN\TN_Core\Service;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;
use TN\TN_Core\Model\User\UserRefreshToken;
use TN\TN_Core\Model\User\UserToken;

/**
 * Generic refresh-token lifecycle service.
 * Keeps framework auth modes available while enabling access-token + refresh-cookie flows.
 */
class RefreshTokenService
{
    private const DEFAULT_COOKIE_NAME = 'TN_refresh';
    private const DEFAULT_REFRESH_TTL = Time::ONE_MONTH * 6;

    private static function shouldDisableLegacyTokenCookie(): bool
    {
        return trim((string) ($_ENV['AUTH_DISABLE_LEGACY_TOKEN_COOKIE'] ?? '0')) === '1';
    }

    private static function getLegacyTokenCookieName(): string
    {
        $name = trim((string) ($_ENV['AUTH_LEGACY_TOKEN_COOKIE_NAME'] ?? 'TN_token'));
        return $name !== '' ? $name : 'TN_token';
    }

    private static function clearLegacyTokenCookieIfDisabled(): void
    {
        if (!self::shouldDisableLegacyTokenCookie()) {
            return;
        }
        $request = HTTPRequest::get();
        $request->setCookie(self::getLegacyTokenCookieName(), '', [
            'expires' => Time::getNow() - Time::ONE_DAY,
            'secure' => $_ENV['ENV'] !== 'development',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function getCookieName(): string
    {
        $name = trim((string) ($_ENV['AUTH_REFRESH_COOKIE_NAME'] ?? self::DEFAULT_COOKIE_NAME));
        return $name !== '' ? $name : self::DEFAULT_COOKIE_NAME;
    }

    public static function getRefreshTtl(): int
    {
        $ttl = (int) ($_ENV['AUTH_REFRESH_TOKEN_TTL'] ?? self::DEFAULT_REFRESH_TTL);
        return $ttl > 0 ? $ttl : self::DEFAULT_REFRESH_TTL;
    }

    public static function getCookieTokenFromRequest(): ?string
    {
        $request = HTTPRequest::get();
        $raw = $request->getCookie(self::getCookieName());
        if ($raw === null) {
            return null;
        }
        $trimmed = trim($raw);
        return $trimmed !== '' ? $trimmed : null;
    }

    public static function setRefreshCookie(string $rawToken): void
    {
        $request = HTTPRequest::get();
        $request->setCookie(self::getCookieName(), $rawToken, [
            'expires' => Time::getNow() + self::getRefreshTtl(),
            'secure' => $_ENV['ENV'] !== 'development',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    public static function clearRefreshCookie(): void
    {
        $request = HTTPRequest::get();
        $request->setCookie(self::getCookieName(), '', [
            'expires' => Time::getNow() - Time::ONE_DAY,
            'secure' => $_ENV['ENV'] !== 'development',
            'domain' => $_ENV['COOKIE_DOMAIN'],
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    /**
     * Create a fresh refresh token for user and set the cookie.
     */
    public static function issueForUser(
        User $user,
        ?int $twoFaVerifiedAt = null,
        ?string $csrfSecret = null
    ): string
    {
        self::clearLegacyTokenCookieIfDisabled();
        $rawToken = UserRefreshToken::createForUser(
            $user,
            self::getRefreshTtl(),
            $twoFaVerifiedAt,
            $csrfSecret
        );
        self::setRefreshCookie($rawToken);
        return $rawToken;
    }

    public static function revokeFromRequestCookie(): void
    {
        self::clearLegacyTokenCookieIfDisabled();
        $raw = self::getCookieTokenFromRequest();
        if ($raw !== null) {
            UserRefreshToken::revokeByRawToken($raw);
        }
        self::clearRefreshCookie();
    }

    /**
     * Rotate refresh cookie token and return a fresh access token user.
     * Returns null when no valid refresh token cookie exists.
     */
    public static function rotateFromRequestCookieAndIssueAccessToken(): ?User
    {
        $raw = self::getCookieTokenFromRequest();
        if ($raw === null) {
            return null;
        }

        $existing = UserRefreshToken::findValidByRawToken($raw);
        if (!$existing instanceof UserRefreshToken) {
            self::clearRefreshCookie();
            return null;
        }

        $user = User::readFromId($existing->userId, true);
        if (!$user instanceof User) {
            $existing->revoke();
            self::clearRefreshCookie();
            return null;
        }

        // One-time use refresh token: revoke old row, issue new row.
        $existing->revoke();
        self::issueForUser($user, $existing->twoFaVerifiedAt, $existing->csrfSecret);
        $user->issueAccessToken(true, $existing->twoFaVerifiedAt, $existing->csrfSecret);

        return $user;
    }

    /**
     * Persist the current access token's 2FA trust onto the active refresh token cookie row.
     * This keeps staff trust across access-token rotations.
     */
    public static function syncTwoFactorTrustFromAccessToken(UserToken $userToken): void
    {
        if ($userToken->twoFaVerifiedAt === null || $userToken->csrfSecret === null || $userToken->csrfSecret === '') {
            return;
        }
        $raw = self::getCookieTokenFromRequest();
        if ($raw === null) {
            return;
        }
        $refreshToken = UserRefreshToken::findValidByRawToken($raw);
        if (!$refreshToken instanceof UserRefreshToken) {
            return;
        }
        $refreshToken->update([
            'twoFaVerifiedAt' => $userToken->twoFaVerifiedAt,
            'csrfSecret' => $userToken->csrfSecret,
        ]);
    }
}

