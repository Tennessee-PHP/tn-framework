<?php

namespace TN\TN_Core\Service;

use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Storage\Redis;

/**
 * Fixed calendar-hour rate limit for public {@code api/insider/signup} (lead capture).
 * Uses Redis counters; fails open if Redis is unavailable (same spirit as {@see RateLimitService}).
 */
class InsiderSignupRateLimitService
{
    /** Max successful rate-limit checks (POST attempts) per client IP per UTC calendar hour. */
    public const MAX_REQUESTS_PER_HOUR_PER_IP = 5;

    private const KEY_PREFIX = 'ratelimit:insider:signup:';

    public static function isAllowed(HTTPRequest $request): bool
    {
        try {
            $redis = Redis::getInstance(true);
        } catch (\Throwable $e) {
            error_log('InsiderSignupRateLimit: Redis unavailable, allowing request: ' . $e->getMessage());
            return true;
        }

        $ip = $request->getClientIp();
        if ($ip === '') {
            $ip = 'unknown';
        }

        $bucket = date('Y-m-d-H');
        $key = self::KEY_PREFIX . $bucket . ':' . $ip;

        $now = time();
        $endOfHour = (int) strtotime(date('Y-m-d H:00:00', $now)) + 3600;
        $ttl = max(1, $endOfHour - $now);

        try {
            $count = $redis->incr($key);
            if ($count === 1) {
                $redis->expire($key, $ttl);
            }
        } catch (\Throwable $e) {
            error_log('InsiderSignupRateLimit: Redis error, allowing request: ' . $e->getMessage());
            return true;
        }

        return $count <= self::MAX_REQUESTS_PER_HOUR_PER_IP;
    }
}
