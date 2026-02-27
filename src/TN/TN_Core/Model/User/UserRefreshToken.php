<?php

namespace TN\TN_Core\Model\User;

use Random\RandomException;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonOperator;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Storage\DB;
use TN\TN_Core\Model\Time\Time;

/**
 * Refresh token rows for long-lived browser sessions.
 * Stores only a hash of the refresh token; callers should keep the raw token in HttpOnly cookie only.
 */
#[TableName('user_refresh_tokens')]
class UserRefreshToken implements Persistence
{
    use MySQL;
    use PersistentModel;

    public int $userId;
    public string $tokenHash;
    public int $createdTs;
    public int $expiresTs;
    public ?int $revokedAt = null;
    public ?int $twoFaVerifiedAt = null;
    public ?string $csrfSecret = null;

    private static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    /**
     * @throws RandomException
     */
    public static function createForUser(
        User $user,
        int $ttlSeconds,
        ?int $twoFaVerifiedAt = null,
        ?string $csrfSecret = null
    ): string
    {
        $now = Time::getNow();
        $rawToken = bin2hex(random_bytes(64));
        $validTwoFaTs = null;
        if ($twoFaVerifiedAt !== null && ($twoFaVerifiedAt + Time::ONE_MONTH) > $now) {
            $validTwoFaTs = $twoFaVerifiedAt;
        }

        $refreshToken = self::getInstance();
        $refreshToken->userId = $user->id;
        $refreshToken->tokenHash = self::hashToken($rawToken);
        $refreshToken->createdTs = $now;
        $refreshToken->expiresTs = $now + $ttlSeconds;
        $refreshToken->revokedAt = null;
        $refreshToken->twoFaVerifiedAt = $validTwoFaTs;
        $refreshToken->csrfSecret = $validTwoFaTs !== null ? $csrfSecret : null;
        $refreshToken->save();

        return $rawToken;
    }

    public static function findValidByRawToken(string $rawToken): ?UserRefreshToken
    {
        $trimmed = trim($rawToken);
        if ($trimmed === '') {
            return null;
        }
        $now = Time::getNow();
        $hash = self::hashToken($trimmed);
        $results = self::search(new SearchArguments(
            conditions: [
                new SearchLogical('AND', [
                    new SearchComparison('`tokenHash`', '=', $hash),
                    new SearchComparison('`expiresTs`', '>', $now),
                    new SearchComparison('`revokedAt`', SearchComparisonOperator::IsNull, null),
                ]),
            ],
            limit: new SearchLimit(0, 1)
        ));

        return $results !== [] ? $results[0] : null;
    }

    public static function revokeByRawToken(string $rawToken): void
    {
        $trimmed = trim($rawToken);
        if ($trimmed === '') {
            return;
        }
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $stmt = $db->prepare("UPDATE `{$table}` SET `revokedAt` = ? WHERE `tokenHash` = ? AND `revokedAt` IS NULL");
        $stmt->execute([Time::getNow(), self::hashToken($trimmed)]);
    }

    public static function revokeAllForUser(int $userId): void
    {
        $db = DB::getInstance($_ENV['MYSQL_DB'], true);
        $table = self::getTableName();
        $stmt = $db->prepare("UPDATE `{$table}` SET `revokedAt` = ? WHERE `userId` = ? AND `revokedAt` IS NULL");
        $stmt->execute([Time::getNow(), $userId]);
    }

    public function revoke(): void
    {
        if ($this->revokedAt !== null) {
            return;
        }
        $this->update([
            'revokedAt' => Time::getNow(),
        ]);
    }
}

