<?php

namespace TN\TN_Core\Model\CORS;

/**
 * CORS origin whitelist helper. Returns the allowed origin only when the request origin is in CORS_ALLOWED_ORIGINS.
 * Order: HTTP_ORIGIN (best), then HTTP_X_FORWARDED_ORIGIN (set by Cloudflare Transform Rule when Origin is stripped),
 * then origin derived from HTTP_REFERER as last resort.
 */
class CORS
{
    /**
     * Get the origin to send in Access-Control-Allow-Origin, or null if the request origin is not whitelisted.
     * Reads CORS_ALLOWED_ORIGINS (comma-separated) from env.
     */
    public static function getAllowedOrigin(): ?string
    {
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
        if ($requestOrigin === '') {
            $requestOrigin = $_SERVER['HTTP_X_FORWARDED_ORIGIN'] ?? '';
        }
        if ($requestOrigin === '') {
            $requestOrigin = self::originFromReferer();
        }
        if ($requestOrigin === '') {
            return null;
        }

        $allowed = $_ENV['CORS_ALLOWED_ORIGINS'] ?? '';
        if ($allowed === '') {
            return null;
        }

        $list = array_filter(array_map('trim', explode(',', $allowed)));
        return in_array($requestOrigin, $list, true) ? $requestOrigin : null;
    }

    /**
     * Derive origin (scheme + host) from Referer when Origin header is not forwarded by proxy.
     */
    private static function originFromReferer(): string
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer === '') {
            return '';
        }
        $parsed = parse_url($referer);
        if ($parsed === false || !isset($parsed['host'])) {
            return '';
        }
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'];
        $port = $parsed['port'] ?? null;
        $defaultPort = ($scheme === 'https') ? 443 : 80;
        if ($port !== null && (int) $port !== $defaultPort) {
            return $scheme . '://' . $host . ':' . $port;
        }
        return $scheme . '://' . $host;
    }
}
