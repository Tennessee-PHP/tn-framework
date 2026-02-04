<?php

namespace TN\TN_Core\Model\User;

use Random\RandomException;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\Email\Email;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * One-time 6-digit login code sent by email. Invalidates previous codes when a new one is created.
 */
#[TableName('users_login_codes')]
class LoginCode implements Persistence
{
    use MySQL;
    use PersistentModel;

    public const int EXPIRES_AFTER_SECONDS = 900; // 15 minutes

    public int $userId;
    public string $code;
    public int $generatedTs;
    public int $expiresTs;
    public bool $used = false;
    public bool $invalidated = false;

    /**
     * Create a new login code for the user, invalidate any existing codes, and send the code by email.
     *
     * @throws RandomException
     * @throws ValidationException
     */
    public static function createForUser(User $user): void
    {
        self::invalidateAllForUser($user->id);
        $now = Time::getNow();
        $lc = self::getInstance();
        $lc->userId = $user->id;
        $lc->code = (string) random_int(100000, 999999);
        $lc->generatedTs = $now;
        $lc->expiresTs = $now + self::EXPIRES_AFTER_SECONDS;
        $lc->used = false;
        $lc->invalidated = false;
        $lc->save();
        Email::sendFromTemplate(
            'user/logincode',
            $user->email,
            [
                'username' => $user->username,
                'code' => $lc->code,
            ]
        );
    }

    /**
     * Mark all login codes for this user as invalidated.
     */
    public static function invalidateAllForUser(int $userId): void
    {
        $existing = self::search(new SearchArguments(
            conditions: [new SearchComparison('`userId`', '=', $userId)]
        ));
        foreach ($existing as $lc) {
            if (!$lc->invalidated) {
                $lc->update(['invalidated' => true]);
            }
        }
    }

    /**
     * Find a valid (unused, not invalidated, not expired) login code for the user and code string.
     */
    public static function findValidForUserAndCode(int $userId, string $code): ?LoginCode
    {
        $now = Time::getNow();
        $results = self::search(new SearchArguments(
            conditions: [
                new SearchLogical('AND', [
                    new SearchComparison('`userId`', '=', $userId),
                    new SearchComparison('`code`', '=', $code),
                    new SearchComparison('`used`', '=', false),
                    new SearchComparison('`invalidated`', '=', false),
                    new SearchComparison('`expiresTs`', '>', $now),
                ])
            ]
        ));
        return $results !== [] ? $results[0] : null;
    }

    public function getUser(): User
    {
        return User::readFromId($this->userId);
    }
}
