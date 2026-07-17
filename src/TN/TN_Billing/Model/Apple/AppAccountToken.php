<?php

namespace TN\TN_Billing\Model\Apple;

use Ramsey\Uuid\Uuid as RamseyUuid;
use TN\TN_Core\Attribute\MySQL\Index;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Core\Model\User\User;

/**
 * Maps a StoreKit appAccountToken (UUID) to a Footballguys user for Apple webhook linking.
 */
#[TableName('apple_app_account_tokens')]
class AppAccountToken implements Persistence
{
    use MySQL;
    use PersistentModel;

    #[Index('idx_token')]
    public string $token = '';

    #[Index('idx_user_id')]
    public int $userId = 0;

    public int $createdTs = 0;

    public int $lastUsedTs = 0;

    /**
     * Return the active token for this user, creating one if needed (one active token per user).
     *
     * @throws ValidationException
     */
    public static function getOrCreateForUser(User $user): AppAccountToken
    {
        $existing = self::search(new SearchArguments(
            conditions: [new SearchComparison('`userId`', '=', $user->id)]
        ));
        if ($existing !== []) {
            /** @var AppAccountToken $token */
            $token = $existing[0];
            $token->update(['lastUsedTs' => Time::getNow()]);
            return $token;
        }

        $token = self::getInstance();
        $token->update([
            'token' => (string)RamseyUuid::uuid4(),
            'userId' => $user->id,
            'createdTs' => Time::getNow(),
            'lastUsedTs' => Time::getNow(),
        ]);
        return $token;
    }

    public static function readFromToken(string $token): ?AppAccountToken
    {
        $token = trim($token);
        if ($token === '') {
            return null;
        }
        $matches = self::searchByProperty('token', $token);
        return count($matches) > 0 ? $matches[0] : null;
    }

    public function getUser(): ?User
    {
        $user = User::readFromId($this->userId);
        return $user instanceof User ? $user : null;
    }
}
