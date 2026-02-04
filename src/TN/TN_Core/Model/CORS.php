<?php

namespace TN\TN_Core\Model;

/**
 * Central CORS handling using CORS_ALLOWED_ORIGINS from env.
 * Sets Access-Control-Allow-Origin only when the request origin is in the allowlist.
 */
class CORS
{
    /**
     * Parse comma-separated CORS_ALLOWED_ORIGINS from env into an array of trimmed origins.
     *
     * @return list<string>
     */
    public static function getAllowlist(): array
    {
        $raw = $_ENV['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: '';
        if ($raw === '') {
            return [];
        }
        $list = array_map('trim', explode(',', $raw));

        return array_values(array_filter($list, fn(string $o) => $o !== ''));
    }

    /**
     * Send CORS headers only if the request origin is in the allowlist.
     * Never echoes the client origin; never uses * with credentials.
     *
     * @return void
     */
    public static function applyCorsHeaders(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($origin === '') {
            return;
        }

        $allowlist = self::getAllowlist();
        if (!in_array($origin, $allowlist, true)) {
            return;
        }

        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
    }
}
