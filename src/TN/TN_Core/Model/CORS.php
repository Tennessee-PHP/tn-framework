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
            file_put_contents('/var/www/html/.cursor/debug.log', json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H-origin',
                'location' => __FILE__ . ':' . __LINE__,
                'message' => 'CORS skipped: HTTP_ORIGIN empty',
                'data' => [],
                'timestamp' => time() * 1000
            ]) . "\n", FILE_APPEND);
            return;
        }

        $allowlist = self::getAllowlist();
        if (!in_array($origin, $allowlist, true)) {
            file_put_contents('/var/www/html/.cursor/debug.log', json_encode([
                'sessionId' => 'debug-session',
                'runId' => 'run1',
                'hypothesisId' => 'H-allowlist',
                'location' => __FILE__ . ':' . __LINE__,
                'message' => 'CORS skipped: origin not in allowlist',
                'data' => ['origin' => $origin, 'allowlist' => $allowlist],
                'timestamp' => time() * 1000
            ]) . "\n", FILE_APPEND);
            return;
        }

        header("Access-Control-Allow-Origin: $origin");
        header('Access-Control-Allow-Credentials: true');
        file_put_contents('/var/www/html/.cursor/debug.log', json_encode([
            'sessionId' => 'debug-session',
            'runId' => 'run1',
            'hypothesisId' => 'H-cors-set',
            'location' => __FILE__ . ':' . __LINE__,
            'message' => 'CORS headers set',
            'data' => ['origin' => $origin],
            'timestamp' => time() * 1000
        ]) . "\n", FILE_APPEND);
    }
}
