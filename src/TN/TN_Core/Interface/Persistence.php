<?php

namespace TN\TN_Core\Interface;

use TN\TN_Core\Model\PersistentModel\Search\CountAndTotalResult;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;

interface Persistence {
    /** @returns static[] */
    static function searchStorage(SearchArguments $search, bool $absoluteLatest = false): array;
    static function countStorage(SearchArguments $search, bool $absoluteLatest = false): int;
    static function countAndTotal(SearchArguments $search, string $propertyToTotal, bool $absoluteLatest = false): CountAndTotalResult;
    /** @returns static[] */
    static function search(SearchArguments $search, bool $absoluteLatest = false): array;
    static function count(SearchArguments $search, bool $absoluteLatest = false): int;
    static function readFromId(int|string $id, bool $absoluteLatest = false): ?static;
    static function searchOne(SearchArguments $search, bool $absoluteLatest = false): ?static;
    static function searchByProperty(string $property, mixed $value, bool $absoluteLatest = false): array;
    static function searchByProperties(array $propertyValues, bool $absoluteLatest = false): array;

    static function batchSaveInsert(array $objects, bool $useSetId = false): void;
    public static function batchErase(array $objects): void;
    function save(array $changedProperties = []): void;
    function erase(): void;

}