<?php

namespace TN\TN_Core\Model\PersistentModel;

use TN\TN_Core\Model\PersistentModel\Search\CountAndTotalResult;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;

/**
 * detailed ways to search for objects
 */
trait Search
{
    public static function search(SearchArguments $search, bool $absoluteLatest = false): array
    {
        $cacheIdentifier = $search->getCacheIdentifier();
        $result = static::searchCache($cacheIdentifier);
        if ($result !== null) {
            return $result;
        }
        $result = static::searchStorage($search, $absoluteLatest);
        static::searchCacheSet($cacheIdentifier, $result);
        return $result;
    }

    public static function count(SearchArguments $search, bool $absoluteLatest = false): int
    {
        $cacheIdentifier = $search->getCacheIdentifier();
        $count = static::countCache($cacheIdentifier);
        if ($count !== null) {
            return $count;
        }
        $count = static::countStorage($search, $absoluteLatest);
        static::countCacheSet($cacheIdentifier, $count);
        return $count;
    }

    public static function readAll(): array
    {
        return static::search(new SearchArguments());
    }

    public static function countAndTotal(SearchArguments $search, string $propertyToTotal, bool $absoluteLatest = false): CountAndTotalResult
    {
        return static::countAndTotalStorage($search, $propertyToTotal, $absoluteLatest);
    }

    public static function readFromId(int|string $id, bool $absoluteLatest = false): ?static
    {
        $results = static::searchByProperty('id', $id, $absoluteLatest);
        return count($results) > 0 ? $results[0] : null;
    }

    public static function searchOne(SearchArguments $search, bool $absoluteLatest = false): ?static
    {
        if (!$search->limit) {
            $search->limit = new SearchLimit(0, 1);
        }
        $results = static::search($search, $absoluteLatest);
        return count($results) > 0 ? $results[0] : null;
    }

    /**
     * @param string $property
     * @param mixed $value
     * @param bool $absoluteLatest
     * @return static[]
     */
    public static function searchByProperty(string $property, mixed $value, bool $absoluteLatest = false): array
    {
        return static::search(new SearchArguments(
            conditions: [
                new SearchComparison("`{$property}`", '=', $value)
            ]
        ), $absoluteLatest);
    }

    /**
     * @param array $propertyValues
     * @param bool $absoluteLatest
     * @return static[]
     */
    public static function searchByProperties(array $propertyValues, bool $absoluteLatest = false): array
    {
        $conditions = [];
        foreach ($propertyValues as $property => $value) {
            $conditions[] = new SearchComparison("`{$property}`", '=', $value);
        }
        return static::search(new SearchArguments(
            conditions: $conditions
        ), $absoluteLatest);
    }
}
