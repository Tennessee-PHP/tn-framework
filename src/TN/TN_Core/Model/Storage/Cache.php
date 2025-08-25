<?php

namespace TN\TN_Core\Model\Storage;

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
    /** @var string the redis key at which to store the set of cache keys */
    private static string $keysKey = 'Cache::_keys';

    /**
     * set some data in the cache
     * 
     * There is no need to avoid key clashes be prepending an environmentally unique string; this is automatically
     * handled by a prefix constant passed to the client at instantiation.
     * @param string $key the key to store it against
     * @param mixed $value the value to store - can be anything that can be run through php's serialize
     * @param int $lifetime in seconds. Cache data on this key will expire after this time. Default of 0 = forever
     * @see https://www.php.net/manual/en/function.serialize.php
     * @example
     * <code>
     * \TN\Util\Cache::set('articleresult', $articles, 86400);
     * </code>
     */
    public static function set(string $key, mixed $value, int $lifetime = 0)
    {
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();

        // Check if key exists with wrong type
        $type = $client->type($key);
        if ($type !== 'none' && $type !== 'string') {
            $client->del($key);
        }

        $client->set($key, serialize($value));
        $client->sadd(self::$keysKey, [$key]);
        if ($lifetime) {
            $client->expire($key, $lifetime);
        }
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
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        return unserialize($client->get($key));
    }

    public static function setAdd(string $key, string $value, int $lifespan): void
    {
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        $client->sadd($key, [$value]);
        $client->sadd(self::$keysKey, [$key]);
        $client->persist($key);
        $client->expire($key, $lifespan);
    }

    public static function setRemove(string $key, string $value): void
    {
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        $client->srem($key, [$value]);
    }

    public static function setMembers(string $key): array
    {
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        return $client->smembers($key);
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
        $client = Redis::getInstance();
        $client->hset($key, $field, serialize($value));
        $client->sadd(self::$keysKey, [$key]);
        if ($lifetime) {
            $client->expire($key, $lifetime);
        }
    }

    public static function hashGet(string $key, string $field): mixed
    {
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        return unserialize($client->hget($key, $field));
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
        $key = self::getStorageKey($key);
        $client = Redis::getInstance();
        $client->set($key, false);
        $client->del($key);
        $client->srem(self::$keysKey, [$key]);
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
        $client = Redis::getInstance();
        return $client->scard(self::$keysKey);
    }

    /** delete everything in the cache WARNING: this should only be consumed by some kind of admin panel!! */
    public static function deleteAll()
    {
        $client = Redis::getInstance();
        $keys = $client->smembers(self::$keysKey);
        foreach ($keys as $key) {
            $client->del($key);
        }
        $client->del(self::$keysKey);
    }
}
