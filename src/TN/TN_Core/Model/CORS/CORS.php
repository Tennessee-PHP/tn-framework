<?php

namespace TN\TN_Core\Model\CORS;

/**
 * CORS origin whitelist helper. Returns the allowed origin only when the request Origin is in CORS_ALLOWED_ORIGINS.
 */
class CORS
{
    /**
     * Get the origin to send in Access-Control-Allow-Origin, or null if the request origin is not whitelisted.
     * Reads CORS_ALLOWED_ORIGINS (comma-separated) from env. Exact match against HTTP_ORIGIN.
     */
    public static function getAllowedOrigin(): ?string
    {
        $requestOrigin = $_SERVER['HTTP_ORIGIN'] ?? '';
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
}
