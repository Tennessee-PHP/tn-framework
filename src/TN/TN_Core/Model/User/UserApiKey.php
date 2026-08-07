<?php

namespace TN\TN_Core\Model\User;

use Random\RandomException;
use TN\TN_Core\Attribute\Constraints\Strlen;
use TN\TN_Core\Attribute\MySQL\Index;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonOperator;
use TN\TN_Core\Model\PersistentModel\Search\SearchLimit;
use TN\TN_Core\Model\PersistentModel\Search\SearchLogical;
use TN\TN_Core\Model\PersistentModel\Search\SearchSorter;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * Long-lived personal API key for a user. Equivalent to a login access token for auth.
 * Stores only a hash of the key; the raw key is shown once at creation.
 */
#[TableName('user_api_keys')]
class UserApiKey implements Persistence
{
    use MySQL;
    use PersistentModel;

    public const PREFIX = 'fbgak_';
    public const MAX_ACTIVE_KEYS = 10;
    public const DISPLAY_PREFIX_LENGTH = 12;
    private const LAST_USED_TOUCH_INTERVAL = Time::ONE_HOUR;

    public const LIFETIME_1_WEEK = Time::ONE_WEEK;
    public const LIFETIME_1_MONTH = Time::ONE_MONTH;
    public const LIFETIME_3_MONTHS = Time::ONE_MONTH * 3;
    public const LIFETIME_6_MONTHS = Time::ONE_MONTH * 6;
    public const LIFETIME_1_YEAR = Time::ONE_YEAR;
    public const DEFAULT_LIFETIME = self::LIFETIME_3_MONTHS;

    /** @var array<int, string> lifetime seconds => label */
    public const ALLOWED_LIFETIMES = [
        self::LIFETIME_1_WEEK => '1 week',
        self::LIFETIME_1_MONTH => '1 month',
        self::LIFETIME_3_MONTHS => '3 months',
        self::LIFETIME_6_MONTHS => '6 months',
        self::LIFETIME_1_YEAR => '1 year',
    ];

    public const EXPIRED_MESSAGE = 'API key has expired. Create a new key from your profile.';

    #[Index('idx_user_api_keys_user')]
    public int $userId;

    #[Strlen(1, 100)]
    public string $label;

    #[Strlen(64, 64)]
    public string $tokenHash;

    #[Strlen(1, 32)]
    public string $prefix;

    public int $createdTs;

    public int $expiresAt;

    public ?int $revokedAt = null;

    public ?int $lastUsedTs = null;

    public static function isPersonalApiKeyToken(string $token): bool
    {
        return str_starts_with($token, self::PREFIX);
    }

    public static function displayPrefixFromToken(string $rawToken): string
    {
        return substr(trim($rawToken), 0, self::DISPLAY_PREFIX_LENGTH);
    }

    private static function hashToken(string $rawToken): string
    {
        return hash('sha256', $rawToken);
    }

    public function isRevoked(): bool
    {
        return $this->revokedAt !== null;
    }

    public function isExpired(?int $now = null): bool
    {
        $now ??= Time::getNow();
        return $this->expiresAt <= $now;
    }

    /**
     * Create a new API key. Returns the raw key once; only the hash is stored.
     *
     * @return array{rawKey: string, apiKey: self}
     * @throws RandomException
     * @throws ValidationException
     */
    public static function createForUser(User $user, string $label, int $lifetimeSeconds = self::DEFAULT_LIFETIME): array
    {
        $label = trim($label);
        if ($label === '') {
            throw new ValidationException('Label is required');
        }
        if (strlen($label) > 100) {
            throw new ValidationException('Label must be 100 characters or fewer');
        }
        if (!isset(self::ALLOWED_LIFETIMES[$lifetimeSeconds])) {
            throw new ValidationException('Invalid key lifetime');
        }
        if (count(self::listActiveForUser($user->id)) >= self::MAX_ACTIVE_KEYS) {
            throw new ValidationException(
                'You already have the maximum of ' . self::MAX_ACTIVE_KEYS . ' active API keys. Revoke one to create another.'
            );
        }

        $rawKey = self::PREFIX . bin2hex(random_bytes(32));
        $now = Time::getNow();

        $apiKey = self::getInstance();
        $apiKey->userId = $user->id;
        $apiKey->label = $label;
        $apiKey->tokenHash = self::hashToken($rawKey);
        $apiKey->prefix = substr($rawKey, 0, self::DISPLAY_PREFIX_LENGTH);
        $apiKey->createdTs = $now;
        $apiKey->expiresAt = $now + $lifetimeSeconds;
        $apiKey->revokedAt = null;
        $apiKey->lastUsedTs = null;
        $apiKey->save();

        return [
            'rawKey' => $rawKey,
            'apiKey' => $apiKey,
        ];
    }

    /**
     * Find a key by raw token regardless of revoked/expired state.
     */
    public static function findByRawToken(string $rawToken): ?self
    {
        $trimmed = trim($rawToken);
        if ($trimmed === '' || !self::isPersonalApiKeyToken($trimmed)) {
            return null;
        }

        $results = self::search(new SearchArguments(
            conditions: [
                new SearchComparison('`tokenHash`', '=', self::hashToken($trimmed)),
            ],
            limit: new SearchLimit(0, 1)
        ));

        return $results !== [] ? $results[0] : null;
    }

    public static function findValidByRawToken(string $rawToken): ?self
    {
        $apiKey = self::findByRawToken($rawToken);
        if ($apiKey === null || $apiKey->isRevoked() || $apiKey->isExpired()) {
            return null;
        }
        return $apiKey;
    }

    /**
     * @return list<self>
     */
    public static function listActiveForUser(int $userId): array
    {
        $now = Time::getNow();
        return self::search(new SearchArguments(
            conditions: [
                new SearchLogical('AND', [
                    new SearchComparison('`userId`', '=', $userId),
                    new SearchComparison('`revokedAt`', SearchComparisonOperator::IsNull, null),
                    new SearchComparison('`expiresAt`', '>', $now),
                ]),
            ],
            sorters: new SearchSorter('createdTs', 'DESC')
        ));
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

    /**
     * Update lastUsedTs at most once per hour to avoid write spam.
     */
    public function recordUse(): void
    {
        $now = Time::getNow();
        if ($this->lastUsedTs !== null && ($now - $this->lastUsedTs) < self::LAST_USED_TOUCH_INTERVAL) {
            return;
        }
        $this->update([
            'lastUsedTs' => $now,
        ]);
    }
}
