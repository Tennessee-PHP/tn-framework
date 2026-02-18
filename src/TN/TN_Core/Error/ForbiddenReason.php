<?php

namespace TN\TN_Core\Error;

/**
 * Holds the reason for the last 403 forbidden (for development debugging).
 * Set in RoleOnly / User before returning FORBIDDEN; read in JSON renderer when ENV=development.
 */
class ForbiddenReason
{
    private static ?array $reason = null;

    public static function set(array $reason): void
    {
        self::$reason = $reason;
    }

    public static function get(): ?array
    {
        $r = self::$reason;
        self::$reason = null;
        return $r;
    }
}
