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
    /**
     * Get the class in the inheritance hierarchy that has the Cache attribute
     * Always prefers parent class Cache attributes over child class Cache attributes
     * to ensure consistent cache keys across the inheritance hierarchy
     * 
     * @return string|null The class name with the Cache attribute, or null if none found
     */
    protected static function getCacheClass(): ?string
    {
        // Walk up the entire inheritance hierarchy to find all classes with Cache attributes
        $class = static::class;
        $cacheClasses = [];
        
        while ($class) {
            $reflection = new \ReflectionClass($class);
            if (!empty($reflection->getAttributes(CacheAttribute::class))) {
                $cacheClasses[] = $class;
            }
            $class = get_parent_class($class);
        }
        
        // If we found cache classes, return the one highest in the hierarchy (parent over child)
        // This ensures child classes always use parent cache keys when parent has Cache attribute
        if (!empty($cacheClasses)) {
            return $cacheClasses[count($cacheClasses) - 1];
        }
        
        return null;
    }

    /** @return bool enable the mysql cache? */
    protected static function cacheEnabled(): bool
    {
        return static::getCacheClass() !== null;
    }

    protected static function getCacheLifespan(): int
    {
        $cacheClass = static::getCacheClass();
        if (!$cacheClass) {
            return Time::ONE_DAY;
        }
        $class = new \ReflectionClass($cacheClass);
        $cacheAttributes = $class->getAttributes(CacheAttribute::class);
        if (empty($cacheAttributes)) {
            return Time::ONE_DAY;
        }
        return $cacheAttributes[0]->newInstance()->lifespan;
    }

    protected static function getCacheVersion(): string
    {
        $cacheClass = static::getCacheClass();
        if (!$cacheClass) {
            return '1.0';
        }
        $class = new \ReflectionClass($cacheClass);
        $cacheAttributes = $class->getAttributes(CacheAttribute::class);
        if (empty($cacheAttributes)) {
            return '1.0';
        }
        return $cacheAttributes[0]->newInstance()->version;
    }

    protected static function getCacheKey(string $type, string $identifier): string
    {
        $cacheClass = static::getCacheClass();
        if (!$cacheClass) {
            // If no cache class found, use static::class as fallback (shouldn't happen if cacheEnabled is checked)
            $cacheClass = static::class;
        }
        return implode(':', [$cacheClass, static::getCacheVersion(), $type, $identifier]);
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

        // Use getCacheKey() which now uses getCacheClass() to ensure consistent cache keys
        // getCacheClass() always returns the parent class Cache attribute when available,
        // ensuring child classes use parent cache keys for consistency
        CacheStorage::delete(static::getCacheKey('object', $this->id));
        CacheStorage::setRemove(static::getCacheKey('set', 'objects'), static::getCacheKey('object', $this->id));

        // Invalidate search/count caches using the cache class
        static::invalidateCacheSet('searches');
        static::invalidateCacheSet('counts');
    }

    /**
     * Get cache status for this model instance comparing MySQL, Redis, and PHP memory
     * 
     * This method checks what's stored in MySQL database, Redis cache, and PHP memory
     * for the specified properties and returns a detailed comparison with checksums.
     * 
     * @param array $properties Array of property names to check (must be persistent properties)
     * @param bool $returnStrings If true, returns array of strings instead of outputting them
     * @return array Array of output strings
     */
    public function getCacheStatus(array $properties, bool $returnStrings = false): array
    {
        $output = [];

        if (empty($this->id)) {
            $msg = "ERROR: Cannot check cache status - object has no ID";
            if ($returnStrings) {
                return [$msg];
            }
            echo $msg . PHP_EOL;
            return [];
        }

        // Filter to only persistent properties
        $persistentProperties = static::getPersistentProperties();
        $validProperties = array_intersect($properties, $persistentProperties);

        if (empty($validProperties)) {
            $msg = "WARNING: No valid persistent properties specified";
            if ($returnStrings) {
                return [$msg];
            }
            echo $msg . PHP_EOL;
            return [];
        }

        $output[] = "=== Cache Status Check for " . static::class . " (ID: {$this->id}) ===";
        $output[] = "Properties checked: " . implode(', ', $validProperties);
        $output[] = "";

        // Get data from PHP memory (current object state)
        $memoryData = [];
        foreach ($validProperties as $prop) {
            $value = $this->$prop ?? null;
            // Process through savePropertyValue to normalize (same as before saving)
            $memoryData[$prop] = $this->savePropertyValue($prop, $value);
        }
        ksort($memoryData);
        $memoryChecksum = md5(json_encode($memoryData));

        // Get data from MySQL database
        $mysqlData = [];
        try {
            $db = \TN\TN_Core\Model\Storage\DB::getInstance($_ENV['MYSQL_DB']);
            $table = static::getTableName();
            $idProp = static::getAutoIncrementProperty();

            $columns = array_map(fn($p) => "`{$p}`", $validProperties);
            $columnsStr = implode(', ', $columns);

            $query = "SELECT {$columnsStr} FROM {$table} WHERE `{$idProp}` = ? LIMIT 1";
            $stmt = $db->prepare($query);
            $stmt->execute([$this->id]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            if ($row) {
                foreach ($validProperties as $prop) {
                    // Load through loadPropertyValue to normalize (same as when reading from DB)
                    $mysqlData[$prop] = $this->loadPropertyValue($prop, $row[$prop] ?? null);
                    // Then process through savePropertyValue for comparison
                    $mysqlData[$prop] = $this->savePropertyValue($prop, $mysqlData[$prop]);
                }
            } else {
                $mysqlData = null; // Row doesn't exist
            }
        } catch (\Exception $e) {
            $mysqlData = ['ERROR' => $e->getMessage()];
        }
        if ($mysqlData !== null && !isset($mysqlData['ERROR'])) {
            ksort($mysqlData);
        }
        $mysqlChecksum = $mysqlData === null ? 'ROW_NOT_FOUND' : (isset($mysqlData['ERROR']) ? 'ERROR' : md5(json_encode($mysqlData)));

        // Get data from Redis cache
        $redisData = [];
        $redisChecksum = 'NOT_CACHED';
        if (static::cacheEnabled()) {
            $cachedObject = static::objectCache($this->id);
            if ($cachedObject) {
                foreach ($validProperties as $prop) {
                    $value = $cachedObject->$prop ?? null;
                    // Process through savePropertyValue to normalize
                    $redisData[$prop] = $cachedObject->savePropertyValue($prop, $value);
                }
                ksort($redisData);
                $redisChecksum = md5(json_encode($redisData));
            }
        }

        // Output comparison
        $output[] = "PHP Memory:";
        $output[] = "  Data: " . json_encode($memoryData, JSON_PRETTY_PRINT);
        $output[] = "  Checksum: {$memoryChecksum}";
        $output[] = "";

        $output[] = "MySQL Database:";
        if ($mysqlData === null) {
            $output[] = "  Status: ROW NOT FOUND";
        } elseif (isset($mysqlData['ERROR'])) {
            $output[] = "  Status: ERROR - " . $mysqlData['ERROR'];
        } else {
            $output[] = "  Data: " . json_encode($mysqlData, JSON_PRETTY_PRINT);
            $output[] = "  Checksum: {$mysqlChecksum}";
        }
        $output[] = "";

        $output[] = "Redis Cache:";
        if (!static::cacheEnabled()) {
            $output[] = "  Status: CACHING DISABLED";
        } elseif (empty($redisData)) {
            $output[] = "  Status: NOT CACHED";
        } else {
            $output[] = "  Data: " . json_encode($redisData, JSON_PRETTY_PRINT);
            $output[] = "  Checksum: {$redisChecksum}";
        }
        $output[] = "";

        // Compare checksums
        $warnings = [];
        if ($mysqlData !== null && !isset($mysqlData['ERROR'])) {
            if ($memoryChecksum !== $mysqlChecksum) {
                $warnings[] = "WARNING: PHP Memory and MySQL Database are OUT OF SYNC!";
            }
            if ($redisChecksum !== 'NOT_CACHED' && $redisChecksum !== 'CACHING_DISABLED' && $redisChecksum !== $mysqlChecksum) {
                $warnings[] = "WARNING: Redis Cache and MySQL Database are OUT OF SYNC!";
            }
            if ($redisChecksum !== 'NOT_CACHED' && $redisChecksum !== 'CACHING_DISABLED' && $memoryChecksum !== $redisChecksum) {
                $warnings[] = "WARNING: PHP Memory and Redis Cache are OUT OF SYNC!";
            }
        }

        if (!empty($warnings)) {
            $output[] = "=== WARNINGS ===";
            foreach ($warnings as $warning) {
                $output[] = $warning;
            }
            $output[] = "";
        } else {
            $output[] = "✓ All sources are in sync";
            $output[] = "";
        }

        $output[] = "=== End Cache Status Check ===";

        if ($returnStrings) {
            return $output;
        }

        foreach ($output as $line) {
            echo $line . PHP_EOL;
        }

        return $output;
    }
}
