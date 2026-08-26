<?php

namespace TN\TN_Core\Model\Storage;

use TN\TN_Core\Trait\PerformanceRecorder;

/**
 * Cache data, predominantly to avoid repeated requests to databases or APIs
 * 
 * The current implementation of our Cache uses redis. This class should always be used rather than interfacing directly
 * with redis, memcached, the local filesystem etc.
 *
 * 
 */
class Cache
{
    use PerformanceRecorder;

    private const string KEY_MATCH_SUFFIX = 'Cache:*';
    private const int SCAN_COUNT = 200;
    /** Used when a caller passes 0 or a negative lifetime. Those must not persist under volatile-lru. */
    private const int DEFAULT_LIFETIME_SECONDS = 3600;

    /** Logical-key fragments that also live on /dev/shm after the first Redis fill. */
    private const array NODE_SHM_PREFIXES = [
        'getProjectionsSetsv3:',
        'api-response:',
        'page-entry-listing:',
        'projections-set-blc:',
    ];

    /**
     * set some data in the cache
     * 
     * There is no need to avoid key clashes be prepending an environmentally unique string; this is automatically
     * handled by a prefix constant passed to the client at instantiation.
     * @param string $key the key to store it against
     * @param mixed $value the value to store - can be anything that can be run through php's serialize
     * @param int $lifetime seconds until Redis drops the key. Default 3600. Zero or negative uses 3600
     *     (not forever: this Redis uses volatile-lru, so a TTL-less key is never evicted).
     * @see https://www.php.net/manual/en/function.serialize.php
     * @example
     * <code>
     * \TN\Util\Cache::set('articleresult', $articles, 86400);
     * </code>
     */
    public static function set(string $key, mixed $value, int $lifetime = 3600)
    {
        $lifetime = self::positiveLifetimeSeconds($lifetime);
        self::dropLegacyKeyIndex();
        $logicalKey = $key;
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SET {$key}", ['lifetime' => $lifetime]);

        $client = Redis::getInstance(true);

        // Check if key exists with wrong type
        $type = $client->type($key);
        if ($type !== 'none' && $type !== 'string') {
            $client->del($key);
        }

        $client->set($key, serialize($value), 'EX', $lifetime);

        $event?->end();

        if (self::usesNodeShm($logicalKey)) {
            NodeShmCache::set($logicalKey, $value, $lifetime);
        }
    }

    /**
     * gets some data from the cache
     * 
     * @param string $key the key to fetch
     * @param bool $write if true, read from the primary (write-then-read / lock poll)
     * @return mixed first unserialized so should always be exactly the same as was passed into the set function
     * @see https://www.php.net/manual/en/function.unserialize.php
     * @example
     * <code>
     * $articles = \TN\Util\Cache::get('articleresult');
     * if (!$articles) { // articles didn't exist or had expired; must fetch from original source...
     * </code>
     */
    public static function get(string $key, bool $write = false): mixed
    {
        $logicalKey = $key;
        if (self::usesNodeShm($logicalKey)) {
            $shmEvent = (new self())->startPerformanceEvent('NodeCache', "GET {$logicalKey}");
            $shm = NodeShmCache::get($logicalKey);
            $shmHit = $shm !== false && $shm !== null;
            $shmEvent?->setMetadata(['hit' => $shmHit, 'miss' => !$shmHit]);
            $shmEvent?->end();
            if ($shmHit) {
                return $shm;
            }
        }

        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "GET {$key}");

        $client = Redis::getInstance($write);
        $data = $client->get($key);

        // Handle null/false values from Redis to avoid unserialize deprecation warning
        if ($data === null || $data === false) {
            $result = false;
        } else {
            $result = unserialize($data);
        }

        // Add hit/miss information to performance tracking
        $isHit = $result !== false && $result !== null;
        $event?->setMetadata(['hit' => $isHit, 'miss' => !$isHit]);
        $event?->end();

        if ($isHit && self::usesNodeShm($logicalKey)) {
            $ttl = (int) $client->ttl($key);
            $lifetime = $ttl > 0 ? $ttl : 3600;
            if ($ttl !== -2) {
                NodeShmCache::set($logicalKey, $result, $lifetime);
            }
        }

        return $result;
    }

    /**
     * Get multiple cache values at once using Redis MGET
     * 
     * @param array $keys Array of cache keys to fetch
     * @return array Associative array with keys as requested and values as unserialized data
     */
    public static function mget(array $keys): array
    {
        if (empty($keys)) {
            return [];
        }

        // Convert keys to storage keys
        $storageKeys = [];
        $keyMapping = [];
        foreach ($keys as $key) {
            $storageKey = self::getStorageKey($key);
            $storageKeys[] = $storageKey;
            $keyMapping[$storageKey] = $key;
        }

        $event = (new self())->startPerformanceEvent('Redis', "MGET " . implode(' ', $storageKeys));

        $client = Redis::getInstance(false);
        $results = $client->mget($storageKeys);

        // Process results and map back to original keys
        $output = [];
        $hits = 0;
        $misses = 0;

        foreach ($results as $index => $result) {
            $storageKey = $storageKeys[$index];
            $originalKey = $keyMapping[$storageKey];

            if ($result !== false && $result !== null) {
                $output[$originalKey] = unserialize($result);
                $hits++;
            } else {
                $output[$originalKey] = null;
                $misses++;
            }
        }

        // Add hit/miss metadata and end the event
        $event?->setMetadata(['hits' => $hits, 'misses' => $misses, 'keys' => count($keys)]);
        $event?->end();

        return $output;
    }

    public static function setAdd(string $key, string $value, int $lifespan): void
    {
        $lifespan = self::positiveLifetimeSeconds($lifespan);
        self::dropLegacyKeyIndex();
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SADD {$key} {$value}", ['lifespan' => $lifespan]);

        $client = Redis::getInstance(true);
        $client->multi();
        $client->sadd($key, [$value]);
        $client->expire($key, $lifespan);
        $client->exec();

        $event?->end();
    }

    public static function setRemove(string $key, string $value): void
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SREM {$key} {$value}");

        $client = Redis::getInstance(true);
        $client->srem($key, [$value]);

        $event?->end();
    }

    public static function setMembers(string $key): array
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SMEMBERS {$key}");

        $client = Redis::getInstance(false);
        $result = $client->smembers($key);

        $event?->end();
        return $result;
    }

    /**
     * Check whether a value is a member of the set at key
     * @param string $key set key
     * @param string $value member to check
     * @return bool true if value is in the set
     */
    public static function setMembersContains(string $key, string $value): bool
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SISMEMBER {$key} {$value}");

        $client = Redis::getInstance(false);
        $result = (bool) $client->sismember($key, $value);

        $event?->end();
        return $result;
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @param int $lifetime seconds until Redis drops the hash. Zero or negative uses 3600, not forever.
     * @return void
     */
    public static function hashSet(string $key, string $field, mixed $value, int $lifetime = 0): void
    {
        $lifetime = self::positiveLifetimeSeconds($lifetime);
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "HSET {$key} {$field}", ['lifetime' => $lifetime]);

        $client = Redis::getInstance(true);
        $client->multi();
        $client->hset($key, $field, serialize($value));
        $client->expire($key, $lifetime);
        $client->exec();

        $event?->end();
    }

    public static function hashGet(string $key, string $field): mixed
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "HGET {$key} {$field}");

        $client = Redis::getInstance(false);
        $result = unserialize($client->hget($key, $field));

        // Add hit/miss information to performance tracking
        $isHit = $result !== false && $result !== null;
        $event?->setMetadata(['hit' => $isHit, 'miss' => !$isHit]);
        $event?->end();

        return $result;
    }

    /**
     * Get a cached value, or have one worker build it and set it (single-flight).
     *
     * Callers pass the build step. This class only gets, locks, calls that function, and sets.
     *
     * @param callable(): mixed $producer
     * @param callable(mixed): bool|null $isHit custom hit test; default is not false and not null
     */
    public static function getOrSet(
        string $key,
        int $lifetimeSeconds,
        callable $producer,
        int $lockSeconds = 60,
        int $waitMs = 15000,
        ?callable $isHit = null
    ): mixed {
        $isHit = $isHit ?? [self::class, 'defaultIsHit'];
        $lockSeconds = max(1, $lockSeconds);
        $waitMs = max(0, $waitMs);

        // Fast path: replica. Miss/stale falls through; the post-lock primary
        // get below is the correctness check (APICachedJSON / Draft Dominator).
        $value = self::get($key);
        if ($isHit($value)) {
            return $value;
        }

        $lockKey = $key . ':rebuild-lock';
        $token = self::tryLock($lockKey, $lockSeconds);
        if ($token !== false) {
            try {
                $value = self::get($key, true);
                if ($isHit($value)) {
                    return $value;
                }
                $value = $producer();
                if ($isHit($value)) {
                    self::set($key, $value, $lifetimeSeconds);
                }
                return $value;
            } finally {
                self::unlock($lockKey, $token);
            }
        }

        $attempts = (int) ceil($waitMs / 100);
        for ($i = 0; $i < $attempts; $i++) {
            usleep(100000);
            $value = self::get($key, true);
            if ($isHit($value)) {
                return $value;
            }
        }

        $value = $producer();
        if ($isHit($value)) {
            self::set($key, $value, $lifetimeSeconds);
        }
        return $value;
    }

    protected static function defaultIsHit(mixed $value): bool
    {
        return $value !== false && $value !== null;
    }

    /**
     * Acquire a short-lived Redis lock (SET NX EX). Returns a token to pass to unlock(), or false.
     */
    public static function tryLock(string $key, int $lifetimeSeconds): string|false
    {
        $lifetimeSeconds = max(1, $lifetimeSeconds);
        $storageKey = self::getStorageKey($key);
        $token = bin2hex(random_bytes(16));
        $event = (new self())->startPerformanceEvent('Redis', "SET NX EX {$storageKey}", ['lifetime' => $lifetimeSeconds]);

        $client = Redis::getInstance(true);
        $result = $client->set($storageKey, $token, 'EX', $lifetimeSeconds, 'NX');

        $event?->end();
        if ($result === null || $result === false) {
            return false;
        }
        return $token;
    }

    /**
     * Release a lock acquired by tryLock(). No-op if the token does not match.
     */
    public static function unlock(string $key, string $token): void
    {
        if ($token === '') {
            return;
        }
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "UNLOCK {$storageKey}");

        $client = Redis::getInstance(true);
        $current = $client->get($storageKey);
        if ($current === $token) {
            $client->del($storageKey);
        }

        $event?->end();
    }

    /**
     * Raw Redis integer for a generation key. Missing key is 1; does not write.
     * @param string $key generation key (not serialized)
     * @return int
     */
    public static function generation(string $key): int
    {
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "GET INT {$storageKey}");

        $value = Redis::getInstance(true)->get($storageKey);

        $event?->end();
        if ($value === null || $value === false || $value === '') {
            return 1;
        }
        return max(1, (int) $value);
    }

    /**
     * Increment a generation key. Missing key becomes 2.
     * @param string $key generation key (not serialized)
     * @return int the new generation
     */
    public static function bumpGeneration(string $key): int
    {
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "INCR {$storageKey}");

        $client = Redis::getInstance(true);
        if ((int) $client->exists($storageKey) === 0) {
            $client->set($storageKey, '2');
            $event?->end();
            return 2;
        }
        $next = (int) $client->incr($storageKey);

        $event?->end();
        return $next;
    }

    /**
     * Remove a key without serialize. Safe for leftover Redis sets.
     * @param string $key
     */
    public static function unlink(string $key): void
    {
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "UNLINK {$storageKey}");

        $client = Redis::getInstance(true);
        try {
            $client->unlink($storageKey);
        } catch (\Throwable) {
            $client->del($storageKey);
        }

        $event?->end();
    }

    /**
     * deletes a key from the cache
     * @param string $key the key to remove
     * <code>
     * \TN\Util\Cache::delete('articleresult');
     * </code>
     */
    public static function delete(string $key): void
    {
        $logicalKey = $key;
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "DEL {$key}");

        $client = Redis::getInstance(true);
        $client->set($key, false);
        $client->del($key);

        $event?->end();

        if (self::usesNodeShm($logicalKey)) {
            NodeShmCache::delete($logicalKey);
        }
    }

    /**
     * @return list<string>
     */
    public static function nodeShmPrefixes(): array
    {
        return self::NODE_SHM_PREFIXES;
    }

    public static function usesNodeShm(string $key): bool
    {
        foreach (self::NODE_SHM_PREFIXES as $prefix) {
            if (str_contains($key, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Redis TTL in seconds. Zero/negative is treated as DEFAULT_LIFETIME_SECONDS so
     * volatile-lru can evict the key; a TTL-less write survives until manual delete.
     */
    private static function positiveLifetimeSeconds(int $lifetime): int
    {
        return $lifetime > 0 ? $lifetime : self::DEFAULT_LIFETIME_SECONDS;
    }

    /**
     * treat the key to avoid clashes
     * @param string $key
     * @return string
     */
    protected static function getStorageKey(string $key): string
    {
        // Use distinct prefixes for different Redis data types
        if (str_contains($key, ':set:')) {
            return 'Cache:sets:' . $key;
        } else if (str_contains($key, ':hash:')) {
            return 'Cache:hashes:' . $key;
        } else {
            return 'Cache:strings:' . $key;
        }
    }

    /**
     * Live Cache:* keys currently stored in Redis (expired keys are not counted).
     * @return int
     */
    public static function getCacheKeysSize(): int
    {
        self::dropLegacyKeyIndex();
        $event = (new self())->startPerformanceEvent('Redis', 'SCAN COUNT Cache:*');

        $count = 0;
        foreach (self::eachCacheStorageKey() as $_) {
            $count++;
        }

        $event?->end();
        return $count;
    }

    /**
     * Delete every live Cache:* key. Staff cache-clear and test rollback only.
     * Does not FLUSHALL / FLUSHDB, so sessions and other Redis keys are left alone.
     */
    public static function deleteAll(): void
    {
        self::dropLegacyKeyIndex();
        $event = (new self())->startPerformanceEvent('Redis', 'SCAN DEL Cache:* (deleteAll)');

        $client = Redis::getInstance(true);
        $batch = [];
        foreach (self::eachCacheStorageKey(true) as $storageKey) {
            $batch[] = $storageKey;
            if (count($batch) >= self::SCAN_COUNT) {
                $client->del($batch);
                $batch = [];
            }
        }
        if ($batch !== []) {
            $client->del($batch);
        }

        $event?->end();
    }

    /**
     * @return \Generator<int, string> storage keys (no Predis REDIS_PREFIX)
     */
    private static function eachCacheStorageKey(bool $write = false): \Generator
    {
        $pattern = ($_ENV['REDIS_PREFIX'] ?? '') . self::KEY_MATCH_SUFFIX;
        foreach (Redis::getInstance($write) as $nodeClient) {
            $cursor = '0';
            do {
                $result = $nodeClient->scan($cursor, [
                    'MATCH' => $pattern,
                    'COUNT' => self::SCAN_COUNT,
                ]);
                $cursor = (string) ($result[0] ?? '0');
                foreach ($result[1] ?? [] as $redisKey) {
                    yield self::storageKeyFromRedisKey((string) $redisKey);
                }
            } while ($cursor !== '0');
        }
    }

    private static function storageKeyFromRedisKey(string $redisKey): string
    {
        $prefix = $_ENV['REDIS_PREFIX'] ?? '';
        if ($prefix !== '' && str_starts_with($redisKey, $prefix)) {
            return substr($redisKey, strlen($prefix));
        }
        return $redisKey;
    }

    /**
     * The old Cache::_keys set is no longer written. Unlink it so leftover
     * members do not keep using Redis memory until a full cache clear.
     */
    private static function dropLegacyKeyIndex(): void
    {
        static $dropped = false;
        if ($dropped) {
            return;
        }
        $dropped = true;
        $client = Redis::getInstance(true);
        try {
            $client->unlink('Cache::_keys');
        } catch (\Throwable) {
            $client->del('Cache::_keys');
        }
    }
}
