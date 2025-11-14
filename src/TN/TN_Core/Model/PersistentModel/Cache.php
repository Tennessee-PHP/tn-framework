<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Attribute\Cache as CacheAttribute;
use TN\TN_Core\Model\Storage\Cache as CacheStorage;
use TN\TN_Core\Model\Time\Time;

/**
 * gets an instance of a class, first querying the stack for an extended class
 *
 */
trait Cache
{
    /** @return bool enable the mysql cache? */
    protected static function cacheEnabled(): bool
    {
        $class = new \ReflectionClass(static::class);
        return !empty($class->getAttributes(CacheAttribute::class));
    }

    protected static function getCacheLifespan(): int
    {
        $class = new \ReflectionClass(static::class);
        $cacheAttributes = $class->getAttributes(CacheAttribute::class);
        if (empty($cacheAttributes)) {
            return Time::ONE_DAY;
        }
        return $cacheAttributes[0]->newInstance()->lifespan;
    }

    protected static function getCacheVersion(): string
    {
        $class = new \ReflectionClass(static::class);
        $cacheAttributes = $class->getAttributes(CacheAttribute::class);
        if (empty($cacheAttributes)) {
            return '1.0';
        }
        return $cacheAttributes[0]->newInstance()->version;
    }

    protected static function getCacheKey(string $type, string $identifier): string
    {
        return implode(':', [static::class, static::getCacheVersion(), $type, $identifier]);
    }

    protected static function objectSetCache(string|int $id, mixed $object): void
    {
        $cacheKey = static::getCacheKey('object', $id);
        CacheStorage::set($cacheKey, $object, static::getCacheLifespan());
        CacheStorage::setAdd(static::getCacheKey('set', 'objects'), $cacheKey, static::getCacheLifespan());
    }

    protected static function objectCache(string|int $id): mixed
    {
        return CacheStorage::get(static::getCacheKey('object', $id)) ?? null;
    }

    protected static function searchCache(string $searchCacheIdentifier): ?array
    {
        if (!static::cacheEnabled()) {
            return null;
        }

        $ids = CacheStorage::get(static::getCacheKey('search', $searchCacheIdentifier));

        if ($ids === false) {
            return null;
        }

        // Build cache keys for all objects
        $cacheKeys = [];
        foreach ($ids as $id) {
            $cacheKeys[$id] = static::getCacheKey('object', $id);
        }

        // Single MGET call to get all cached objects
        $cachedObjects = CacheStorage::mget(array_values($cacheKeys));

        // Determine which objects are missing from cache
        $missingIds = [];
        $results = [];

        foreach ($ids as $id) {
            $cacheKey = $cacheKeys[$id];
            $cachedObject = $cachedObjects[$cacheKey] ?? null;

            if ($cachedObject) {
                $results[] = $cachedObject;
            } else {
                $missingIds[] = $id;
            }
        }

        // Single bulk query to fill in missing objects (with absoluteLatest=true to avoid recursion)
        if (!empty($missingIds)) {
            $missingObjects = static::readFromIds($missingIds, true);
            foreach ($missingObjects as $object) {
                $results[] = $object;
            }
        }

        return $results;
    }

    protected static function searchCacheSet(string $searchCacheIdentifier, array $results): void
    {
        if (!static::cacheEnabled()) {
            return;
        }

        $cacheKey = static::getCacheKey('search', $searchCacheIdentifier);
        $ids = [];
        foreach ($results as $object) {
            $ids[] = $object->id;

            // Always re-cache objects to ensure they're current
            // This ensures that if an object was updated and cache was invalidated,
            // the fresh object from the database will be cached
            static::objectSetCache($object->id, $object);
        }

        CacheStorage::set($cacheKey, $ids, static::getCacheLifespan());
        CacheStorage::setAdd(static::getCacheKey('set', 'searches'), $cacheKey, static::getCacheLifespan());
    }

    protected static function countCache(string $searchCacheIdentifier): ?int
    {
        if (!static::cacheEnabled()) {
            return null;
        }
        return CacheStorage::get(static::getCacheKey('count', $searchCacheIdentifier)) ?? null;
    }

    protected static function countCacheSet(string $searchCacheIdentifier, int $count): void
    {
        if (!static::cacheEnabled()) {
            return;
        }

        CacheStorage::set(static::getCacheKey('count', $searchCacheIdentifier), $count, static::getCacheLifespan());
        CacheStorage::setAdd(static::getCacheKey('set', 'counts'), static::getCacheKey('count', $searchCacheIdentifier), static::getCacheLifespan());
    }

    protected static function invalidateCacheSet(string $set): void
    {
        try {
            foreach (CacheStorage::setMembers(static::getCacheKey('set', $set)) as $memberCacheKey) {
                CacheStorage::delete($memberCacheKey);
            }
        } catch (\Exception $e) {
            // do nothing
        }

        CacheStorage::delete(static::getCacheKey('set', $set));
    }

    protected static function invalidateClassCache(): void
    {
        if (!static::cacheEnabled()) {
            return;
        }
        static::invalidateCacheSet('objects');
        static::invalidateCacheSet('searches');
        static::invalidateCacheSet('counts');
    }

    protected function invalidateCache(): void
    {
        if (!static::cacheEnabled()) {
            return;
        }

        // Invalidate object cache for the current class
        CacheStorage::delete(static::getCacheKey('object', $this->id));
        CacheStorage::setRemove(static::getCacheKey('set', 'objects'), static::getCacheKey('object', $this->id));

        // Invalidate search/count caches for the current class
        static::invalidateCacheSet('searches');
        static::invalidateCacheSet('counts');

        // CRITICAL FIX: Also invalidate caches for all parent classes in the inheritance hierarchy
        $parentClass = get_parent_class(static::class);
        while ($parentClass && method_exists($parentClass, 'cacheEnabled')) {
            // Invalidate object cache for parent class
            $parentCacheKey = implode(':', [$parentClass, static::getCacheVersion(), 'object', $this->id]);
            CacheStorage::delete($parentCacheKey);
            CacheStorage::setRemove(implode(':', [$parentClass, static::getCacheVersion(), 'set', 'objects']), $parentCacheKey);

            // Invalidate search/count caches for parent class
            $parentClass::invalidateCacheSet('searches');
            $parentClass::invalidateCacheSet('counts');

            $parentClass = get_parent_class($parentClass);
        }
    }
}
