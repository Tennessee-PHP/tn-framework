<?php

namespace TN\TN_Core\Error;
use TN\TN_Core\Model\Storage\Redis as RedisDB;

/**
 * logs instances of TN\Model\Error\LoggedError
 */
class Log
{
    /** @var int how many errors to log */
    private static int $length = 1000;

    private static int $subLength = 10;

    /**
     * get the key for a list in redis
     * @param array $options
     * @return string
     */
    private static function getListKey(array $options = []): string
    {
        return 'TN\Model\Error\Log:all';
    }

    /**
     * @param LoggedError $error
     */
    public static function log(LoggedError $error): void
    {
        return;
        $client = RedisDB::getInstance();
        $key = self::getListKey();
        $client->lpush($key, [serialize($error)]);
        $client->ltrim($key, 0, self::$length);
    }

    public static function read(): array
    {
        $client = RedisDB::getInstance();
        $key = self::getListKey();
        $log = [];
        foreach($client->lrange($key, 0, self::$length) as $item) {
            $log[] = unserialize($item);
        }
        return $log;

    }


}