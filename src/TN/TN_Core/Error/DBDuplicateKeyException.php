<?php

namespace TN\TN_Core\Error;

/**
 * Thrown when a database insert or update violates a unique constraint (duplicate key).
 */
class DBDuplicateKeyException extends DBException
{
    public static function isDuplicateKey(\PDOException $e): bool
    {
        $code = $e->getCode();
        return $code === '23000' || $code === 23000;
    }
}
