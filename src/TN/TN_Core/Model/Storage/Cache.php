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

    /** @var array request-level cache to avoid duplicate Redis calls within same HTTP request */
    private static array $requestCache = [];

    /** @var string the redis key at which to store the set of cache keys */
    private static string $keysKey = 'Cache::_keys';

    /**
     * set some data in the cache
     * 
     * There is no need to avoid key clashes be prepending an environmentally unique string; this is automatically
     * handled by a prefix constant passed to the client at instantiation.
     * @param string $key the key to store it against
     * @param mixed $value the value to store - can be anything that can be run through php's serialize
     * @param int $lifetime in seconds. Cache data on this key will expire after this time. Default of 3600 = 1 hour
     * @see https://www.php.net/manual/en/function.serialize.php
     * @example
     * <code>
     * \TN\Util\Cache::set('articleresult', $articles, 86400);
     * </code>
     */
    public static function set(string $key, mixed $value, int $lifetime = 3600)
    {
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SET {$storageKey}", ['lifetime' => $lifetime]);

        $client = Redis::getInstance();

        // Check if key exists with wrong type
        $type = $client->type($storageKey);
        if ($type !== 'none' && $type !== 'string') {
            $client->del($storageKey);
        }

        $client->set($storageKey, serialize($value));
        $client->sadd(self::$keysKey, [$storageKey]);
        if ($lifetime > 0) {
            $client->expire($storageKey, $lifetime);
        }

        // Update request cache with new value
        self::$requestCache[$storageKey] = $value;

        $event?->end();
    }

    /**
     * gets some data from the cache
     * 
     * @param string $key the key to fetch
     * @return mixed first unserialized so should always be exactly the same as was passed into the set function
     * @see https://www.php.net/manual/en/function.unserialize.php
     * @example
     * <code>
     * $articles = \TN\Util\Cache::get('articleresult');
     * if (!$articles) { // articles didn't exist or had expired; must fetch from original source...
     * </code>
     */
    public static function get(string $key): mixed
    {
        $storageKey = self::getStorageKey($key);

        // Check request-level cache first
        if (isset(self::$requestCache[$storageKey])) {
            return self::$requestCache[$storageKey];
        }

        $event = (new self())->startPerformanceEvent('Redis', "GET {$storageKey}");

        $client = Redis::getInstance();
        $data = $client->get($storageKey);

        // Handle null/false values from Redis to avoid unserialize deprecation warning
        if ($data === null || $data === false) {
            $result = false;
        } else {
            $result = unserialize($data);
        }

        // Store in request cache for future lookups in same request
        self::$requestCache[$storageKey] = $result;

        // Add hit/miss information to performance tracking
        $isHit = $result !== false && $result !== null;
        $event?->setMetadata(['hit' => $isHit, 'miss' => !$isHit]);
        $event?->end();

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

        $client = Redis::getInstance();
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
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SADD {$storageKey} {$value}", ['lifespan' => $lifespan]);

        $client = Redis::getInstance();

        try {
            $client->sadd($storageKey, [$value]);
            $client->sadd(self::$keysKey, [$storageKey]);
            $client->persist($storageKey);
            $client->expire($storageKey, $lifespan);
        } catch (\Predis\Response\ServerException $e) {
            // Handle WRONGTYPE error - key exists with different data type
            if (str_contains($e->getMessage(), 'WRONGTYPE')) {
                // Delete the conflicting key and retry
                $client->del($storageKey);
                $client->sadd($storageKey, [$value]);
                $client->sadd(self::$keysKey, [$storageKey]);
                $client->persist($storageKey);
                $client->expire($storageKey, $lifespan);
            } else {
                // Re-throw other Redis errors
                throw $e;
            }
        }

        $event?->end();
    }

    public static function setRemove(string $key, string $value): void
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SREM {$key} {$value}");

        $client = Redis::getInstance();
        $client->srem($key, [$value]);

        $event?->end();
    }

    public static function setMembers(string $key): array
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "SMEMBERS {$key}");

        $client = Redis::getInstance();
        $result = $client->smembers($key);

        $event?->end();
        return $result;
    }

    /**
     * check if a value exists in a cache set
     * @param string $key the set key (should contain :set: for proper prefixing)
     * @param string $value the value to check
     * @return bool true if value exists in set
     */
    public static function setMembersContains(string $key, string $value): bool
    {
        $members = self::setMembers($key);
        return in_array($value, $members, true);
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @param int $lifetime
     * @return void
     */
    public static function hashSet(string $key, string $field, mixed $value, int $lifetime = 0): void
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "HSET {$key} {$field}", ['lifetime' => $lifetime]);

        $client = Redis::getInstance();
        $client->hset($key, $field, serialize($value));
        if ($lifetime) {
            $client->expire($key, $lifetime);
        }

        $event?->end();
    }

    public static function hashGet(string $key, string $field): mixed
    {
        $key = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "HGET {$key} {$field}");

        $client = Redis::getInstance();
        $result = unserialize($client->hget($key, $field));

        // Add hit/miss information to performance tracking
        $isHit = $result !== false && $result !== null;
        $event?->setMetadata(['hit' => $isHit, 'miss' => !$isHit]);
        $event?->end();

        return $result;
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
        $storageKey = self::getStorageKey($key);
        $event = (new self())->startPerformanceEvent('Redis', "DEL {$storageKey}");

        $client = Redis::getInstance();
        $client->del($storageKey);
        $client->srem(self::$keysKey, [$storageKey]);

        // Remove from request cache
        unset(self::$requestCache[$storageKey]);

        $event?->end();
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

    /** @return int the number of items currently stored in the cache */
    public static function getCacheKeysSize(): int
    {
        $event = (new self())->startPerformanceEvent('Redis', "SCARD " . self::$keysKey);

        $client = Redis::getInstance();
        $result = $client->scard(self::$keysKey);

        $event?->end();
        return $result;
    }

    /** delete everything in the cache WARNING: this should only be consumed by some kind of admin panel!! */
    public static function deleteAll()
    {
        $event = (new self())->startPerformanceEvent('Redis', "FLUSHALL (deleteAll)");

        $client = Redis::getInstance();
        $keys = $client->smembers(self::$keysKey);
        foreach ($keys as $key) {
            $client->del($key);
        }
        $client->del(self::$keysKey);

        // Clear request cache
        self::$requestCache = [];

        $event?->end();
    }
}
