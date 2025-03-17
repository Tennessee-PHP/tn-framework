<?php

namespace TN\TN_Core\Model\Provider;

use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\User\User as TNUser;

abstract class ThirdPartyUser implements Persistence
{
    use MySQL;
    use PersistentModel;

    /** @var int the user id (TN Users) */
    protected int $tnUserId;

    /**
     * if there is one, returns a discord user for the specified user
     * @param TNUser $tnUser
     * @param bool $useWriteUser
     * @return ThirdPartyUser|null
     */
    public static function getFromTnUser(TNUser $tnUser, bool $useWriteUser = false): ?ThirdPartyUser
    {
        $class = get_called_class();
        $results = $class::searchByProperty('tnUserId', $tnUser->id, $useWriteUser);
        return empty($results) ? null : $results[0];
    }

    /** @return TNUser|null gets the TNUser for this discord user */
    public function getTnUser(): ?TNUser
    {
        return TNUser::readFromId($this->tnUserId);
    }

}