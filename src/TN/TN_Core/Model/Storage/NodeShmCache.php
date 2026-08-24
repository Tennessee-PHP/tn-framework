<?php

namespace TN\TN_Core\Model\Storage;

/**
 * Per-box /dev/shm files for a small allowlist of large Cache string values.
 * Redis stays the shared copy; this is a RAM disk fill after the first miss.
 */
class NodeShmCache
{
    private const string SUBDIR = 'fbg-cache';

    public static function dir(): string
    {
        $base = $_ENV['CACHE_SHM_DIR'] ?? '/dev/shm';
        return rtrim((string) $base, '/') . '/' . self::SUBDIR;
    }

    public static function path(string $logicalKey): string
    {
        return self::dir() . '/' . self::filename($logicalKey);
    }

    public static function filename(string $logicalKey): string
    {
        return self::filenamePrefix($logicalKey) . hash('sha256', $logicalKey);
    }

    public static function filenamePrefix(string $logicalKey): string
    {
        foreach (Cache::nodeShmPrefixes() as $prefix) {
            if (str_contains($logicalKey, $prefix)) {
                return str_replace(':', '-', $prefix);
            }
        }
        return 'other-';
    }

    public static function get(string $logicalKey): mixed
    {
        $path = self::path($logicalKey);
        if (!is_file($path)) {
            return false;
        }
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return false;
        }
        $data = @unserialize($raw);
        if (!is_array($data) || !array_key_exists('exp', $data) || !array_key_exists('v', $data)) {
            @unlink($path);
            return false;
        }
        if ((int) $data['exp'] <= time()) {
            @unlink($path);
            return false;
        }
        return $data['v'];
    }

    public static function set(string $logicalKey, mixed $value, int $lifetime): void
    {
        if ($lifetime <= 0 || !Cache::usesNodeShm($logicalKey)) {
            return;
        }
        $dir = self::dir();
        if (!self::ensureDir($dir)) {
            return;
        }
        $path = self::path($logicalKey);
        $tmp = $path . '.' . getmypid() . '.' . bin2hex(random_bytes(4));
        $payload = serialize([
            'exp' => time() + $lifetime,
            'v' => $value,
        ]);
        if (@file_put_contents($tmp, $payload) !== false) {
            if (!@rename($tmp, $path)) {
                @unlink($tmp);
            }
        } else {
            @unlink($tmp);
        }
    }

    public static function delete(string $logicalKey): void
    {
        $path = self::path($logicalKey);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    /**
     * Unlink every shm file written for logical keys that contain $prefix.
     */
    public static function deletePrefix(string $prefix): void
    {
        $dir = self::dir();
        if (!is_dir($dir)) {
            return;
        }
        $filePrefix = str_replace(':', '-', $prefix);
        foreach (glob($dir . '/' . $filePrefix . '*') ?: [] as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }

    private static function ensureDir(string $dir): bool
    {
        if (is_dir($dir)) {
            return is_writable($dir);
        }
        if (!@mkdir($dir, 0777, true) && !is_dir($dir)) {
            return false;
        }
        return is_writable($dir);
    }
}
