<?php

namespace TN\TN_Core\Model\User;

use Random\RandomException;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * Per-session token for a user. Replaces the single users.token column.
 * Each login creates a new row; password change revokes all tokens for that user.
 */
#[TableName('user_tokens')]
class UserToken implements Persistence
{
    use MySQL;
    use PersistentModel;

    public int $userId;
    public string $token;
    public int $createdTs;
    public int $expiresTs;
    public ?int $twoFaVerifiedAt = null;
    public ?string $csrfSecret = null;

    /**
     * Find a valid (non-expired) token row by token string.
     */
    public static function findValidByToken(string $token): ?UserToken
    {
        if (empty($token)) {
            return null;
        }
        $now = Time::getNow();
        $results = self::search(new SearchArguments(
            conditions: [
                new SearchLogical('AND', [
                    new SearchComparison('`token`', '=', $token),
                    new SearchComparison('`expiresTs`', '>', $now),
                ])
            ],
            limit: new SearchLimit(0, 1)
        ));
        return $results !== [] ? $results[0] : null;
    }

    /**
     * Create a new token row for the user and return it. Used at login and for login-as.
     *
     * @throws RandomException
     */
    public static function createForUser(User $user): UserToken
    {
        $now = Time::getNow();
        $expiresTs = $now + User::LOGIN_EXPIRES;
        $tokenString = bin2hex(random_bytes(64));

        $ut = self::getInstance();
        $ut->userId = $user->id;
        $ut->token = $tokenString;
        $ut->createdTs = $now;
        $ut->expiresTs = $expiresTs;
        $ut->twoFaVerifiedAt = null;
        $ut->csrfSecret = null;
        $ut->save();

        return $ut;
    }

    /**
     * Whether this token has passed two-factor verification and that verification is still within the validity window (1 month).
     */
    public function isTwoFactorVerified(): bool
    {
        if ($this->twoFaVerifiedAt === null) {
            return false;
        }
        return ($this->twoFaVerifiedAt + Time::ONE_MONTH) > Time::getNow();
    }

    /**
     * Revoke all tokens for a user (e.g. on password change). Deletes all rows for that userId.
     */
    public static function revokeAllForUser(int $userId): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $stmt = $db->prepare("DELETE FROM `{$table}` WHERE `userId` = ?");
        $stmt->execute([$userId]);
    }
}
