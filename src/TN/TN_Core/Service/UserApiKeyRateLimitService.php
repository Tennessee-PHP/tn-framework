<?php

namespace TN\TN_Core\Service;

use TN\TN_Core\Error\RateLimitExceededException;
use TN\TN_Core\Model\Storage\Redis;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\UserApiKey;

/**
 * Per-key rate limiting for personal API keys. Redis fixed-window counters.
 * Fails open if Redis is unavailable.
 */
class UserApiKeyRateLimitService
{
    private const KEY_PREFIX = 'ratelimit:userapikey:';
    private const LIMIT = 500;
    private const WINDOW_SECONDS = Time::ONE_HOUR;

    /**
     * @param object|null $redisClient Optional Redis-like client for testing (incr, expire, ttl).
     * @throws RateLimitExceededException
     */
    public static function check(UserApiKey $apiKey, ?object $redisClient = null): void
    {
        $useTestClient = $redisClient !== null;
        if (!$useTestClient) {
            try {
                $redisClient = Redis::getInstance();
            } catch (\Throwable $e) {
                error_log('UserApiKeyRateLimitService: Redis unavailable, rate limit skipped: ' . $e->getMessage());
                return;
            }
        }

        $redisKey = self::KEY_PREFIX . $apiKey->id;

        try {
            $count = self::incrementWithExpire($redisClient, $redisKey, self::WINDOW_SECONDS);
            if ($count > self::LIMIT) {
                $retryAfter = self::WINDOW_SECONDS;
                try {
                    $ttl = (int) $redisClient->ttl($redisKey);
                    if ($ttl > 0) {
                        $retryAfter = $ttl;
                    }
                } catch (\Throwable) {
                    // keep window default
                }
                throw new RateLimitExceededException(
                    'API key rate limit exceeded',
                    $retryAfter,
                    null,
                    true,
                    'rate_limit_exceeded'
                );
            }
        } catch (RateLimitExceededException $e) {
            throw $e;
        } catch (\Throwable $e) {
            if ($useTestClient) {
                throw $e;
            }
            error_log('UserApiKeyRateLimitService: Redis error, rate limit skipped: ' . $e->getMessage());
        }
    }

    /**
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
