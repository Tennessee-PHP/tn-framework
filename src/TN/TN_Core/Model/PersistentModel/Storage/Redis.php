<?php

namespace TN\TN_Core\Model\PersistentModel\Storage;
use TN\TN_Core\Model\Storage\Redis as RedisDB;

/**
 * stores the object in redis
 *
 */
trait Redis
{
    /**
     * sets available for this object
     * @return string[]
     */
    protected static function getSets(): array
    {
        return ['all'];
    }

    /**
     * @param string $set
     * @return string
     */
    protected static function getSetKey(string $set = 'all'): string
    {
        return __CLASS__ . '-set:' . $set;
    }

    /**
     * gets the string key that redis stores a specific object at
     * @param string $id
     * @return string
     */
    protected static function getObjectKey(string $id): string
    {
        return __CLASS__ . '-id:' . $id;
    }

    /**
     * loads up an object from redis, given its id
     * @param string|int $id
     * @return mixed
     */
    public static function readFromId(string|int $id): mixed
    {
        $client = RedisDB::getInstance();
        $key = self::getObjectKey($id);
        return unserialize($client->get($key));
    }

    /**
     * loads objects up from a set
     * @param string $set
     * @return array
     */
    public static function getFromSet(string $set): array
    {
        $key = self::getSetKey($set);
        $client = RedisDB::getInstance();
        $ids = $client->smembers($key);
        $objects = [];
        foreach ($ids as $id) {
            $object = self::readFromId($id);
            if ($object) {
                $objects[] = $object;
            }
        }
        return $objects;
    }

    /**
     * get all the objects stored on this class
     * @return array
     */
    public static function readAll(): array
    {
        return self::getFromSet('all');
    }

    /**
     * eradicate this object
     */
    public function erase(): bool
    {
        $client = RedisDB::getInstance();
        $key = self::getObjectKey($this->id);
        $client->del($key);
        $this->erased = true;
        $this->eraseFromSets();
        return true;
    }

    /**
     * classes may wish to implement their own to alter the data or append to it prior to save
     * @param array $changedProperties
     * @return array more changed properties to add!
     */
    protected function beforeSave(array $changedProperties): array
    {
        return [];
    }

    /**
     * saves the object
     */
    public function save(array $changedProperties = []): bool
    {
        $this->beforeSave($changedProperties);
        $client = RedisDB::getInstance();
        $key = self::getObjectKey($this->id);
        $client->set($key, serialize($this));
        $this->saveToSets();
        $this->afterSave();
        return true;
    }

    /**
     * use this e.g. to set an expire key
     */
    protected function afterSave(): void
    {

    }

    /**
     * erase from sets
     */
    private function eraseFromSets()
    {
        $sets = $this->getSets();
        $client = RedisDB::getInstance();
        foreach ($sets as $set) {
            $key = self::getSetKey($set);
            $client->srem($key, $this->id);
        }
    }

    /**
     * save to sets
     */
    private function saveToSets()
    {
        $sets = $this->getSets();
        $client = RedisDB::getInstance();
        foreach ($sets as $set) {
            $key = self::getSetKey($set);
            $client->sadd($key, [ $this->id ]);
        }
    }
}