<?php

namespace TN\TN_Advert\Model;

use TN\TN_Core\Attribute\MySQL\ForeignKey;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

#[TableName('advert_placements')]
class AdvertPlacement implements Persistence
{
    use PersistentModel;
    use MySQL;

    /** @var int id of linked advert */
    #[ForeignKey(Advert::class)]
    public int $advertId;

    /** @var string location key for linked advert */
    public string $spotKey;

    /** erase whatever the database has for the given rankings set ID */
    public static function eraseFromAdvertId(int $advertId): void
    {
        static::batchErase(self::searchByProperty('advertId', $advertId));
    }

}