<?php

namespace TN\TN_Core\Model\PersistentModel\Storage\MySQL;

use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * prunes objects by age
 *
 */
trait MySQLPrune
{
    /**
     * get all the objects stored on this class
     * @return void
     */
    public static function prune(): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $tsProp = self::$tsProp;
        $stmt = $db->prepare("DELETE FROM {$table} WHERE {$tsProp} < ?");
        $stmt->execute([Time::getNow() - static::$lifespan]);
    }
}
