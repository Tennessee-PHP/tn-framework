<?php

namespace TN\TN_Core\Model\User;

use TN\TN_Core\Attribute\Impersistent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * a request to reset a password
 * 
 */
#[TableName('user_inactive_changes')]
class UserInactiveChange implements Persistence
{
    use MySQL;
    use PersistentModel;

    /** properties */

    /** @var int the user's id */
    public int $userId;

    /** @var bool whether this user was changed to active, or changed to inactive */
    public bool $active = false;

    /** @var int the user who affected the change */
    public int $byUserId;

    /** @var string comment supplied by $this->byUserId */
    public string $comment = '';

    /** @var int when did this happen? */
    public int $ts = 0;

    /** @var User */
    #[Impersistent]
    public User $user;

    /*** @var User */
    #[Impersistent]
    public User $byUser;

    /** methods */

    /**
     * returns all changes for a specific user
     * @param User $user
     * @return array
     */
    public static function getUserChanges(User $user): array
    {
        $changes = static::search(new SearchArguments(
            new SearchComparison('`userId`', '=', $user->id),
            new SearchSorter('ts', 'DESC')
        ), true);
        foreach ($changes as &$change) {
            $change->user = $user;
            if (isset($change->byUserId)) {
                $change->byUser = User::readFromId($change->byUserId);
            }
        }
        return $changes;
    }

    /**
     * logs an active change to the user
     * @param User $user
     * @param User $byUser
     * @param bool $active
     * @param string $comment
     * @return void
     * @throws ValidationException
     */
    public static function createAndSave(User $user, User $byUser, bool $active, string $comment = ""): void
    {
        if (!$byUser->hasRole('user-admin') && $user->id !== $byUser->id) {
            throw new ValidationException('You do not have permission to change this user\'s status');
        }
        if ($user->inactive === !$active) {
            throw new ValidationException('Users is already ' . ($active ? 'active' : 'inactive'));
        }

        $user->update([
            'inactive' => !$active
        ]);

        $change = static::getInstance();
        $change->userId = $user->id;
        $change->byUserId = $byUser->id;
        $change->active = $active;
        $change->ts = Time::getNow();
        $change->comment = $comment;
        $change->save();
    }
}
