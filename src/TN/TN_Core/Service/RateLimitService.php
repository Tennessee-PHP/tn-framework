<?php

namespace TN\TN_Core\Service;

use TN\TN_Core\Error\RateLimitExceededException;
use TN\TN_Core\Model\Request\HTTPRequest;
use TN\TN_Core\Model\Storage\Redis;

/**
 * Rate limiting for staff routes: by IP and by token separately.
 * Uses Redis fixed-window counters. Fails open if Redis is unavailable.
 */
class RateLimitService
{
    private const STAFF_PATH_PREFIX = 'staff/';
    private const KEY_PREFIX = 'ratelimit:staff:';
    private const DEFAULT_IP_PER_MIN = 120;
    private const DEFAULT_TOKEN_PER_MIN = 180;
    private const DEFAULT_WINDOW_SECONDS = 60;

    /**
     * Check rate limits for this request. No-op for non-staff paths.
     * @param HTTPRequest $request
     * @param object|null $redisClient Optional Redis-like client for testing (incr, expire). If null, uses Redis::getInstance() and fails open on error.
     * @throws RateLimitExceededException if staff path and either IP or token limit exceeded
     */
    public static function check(HTTPRequest $request, ?object $redisClient = null): void
    {
        $path = ltrim($request->path, '/');
        if (strpos($path, self::STAFF_PATH_PREFIX) !== 0) {
            return;
        }

        $window = (int) ($_ENV['RATE_LIMIT_STAFF_WINDOW_SECONDS'] ?? self::DEFAULT_WINDOW_SECONDS);
        $ipLimit = (int) ($_ENV['RATE_LIMIT_STAFF_IP_PER_MIN'] ?? self::DEFAULT_IP_PER_MIN);
        $tokenLimit = (int) ($_ENV['RATE_LIMIT_STAFF_TOKEN_PER_MIN'] ?? self::DEFAULT_TOKEN_PER_MIN);

        $useTestClient = $redisClient !== null;
        if (!$useTestClient) {
            try {
                $redisClient = Redis::getInstance();
            } catch (\Throwable $e) {
                error_log('RateLimitService: Redis unavailable, rate limit skipped: ' . $e->getMessage());
                return;
            }
        }

        try {
            $ip = $request->getClientIp();
            $ipKey = self::KEY_PREFIX . 'ip:' . $ip;

            $count = self::incrementWithExpire($redisClient, $ipKey, $window);
            if ($count > $ipLimit) {
                throw new RateLimitExceededException('Staff rate limit exceeded (by IP)', $window);
            }

            $token = $request->getAuthToken();
            if ($token !== null && $token !== '') {
                $tokenHash = hash('sha256', $token);
                $tokenKey = self::KEY_PREFIX . 'token:' . $tokenHash;
                $count = self::incrementWithExpire($redisClient, $tokenKey, $window);
                if ($count > $tokenLimit) {
                    throw new RateLimitExceededException('Staff rate limit exceeded (by token)', $window);
                }
            }
        } catch (RateLimitExceededException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($useTestClient) {
                throw $e;
            }
            error_log('RateLimitService: Redis error, rate limit skipped: ' . $e->getMessage());
        }
    }

    /**
     * Increment key, set TTL on first request in window. Returns new count.
     * @param \Predis\Client $client
     */
    private static function incrementWithExpire($client, string $key, int $ttlSeconds): int
    {
        $count = $client->incr($key);
        if ($count === 1) {
            $client->expire($key, $ttlSeconds);
        }
        return $count;
    }
}
