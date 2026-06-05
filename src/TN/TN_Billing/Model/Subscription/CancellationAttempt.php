<?php

namespace TN\TN_Billing\Model\Subscription;

use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\MySQL\Key;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

/**
 * Log of user cancellation recovery wizard attempts (survey, offer, outcome).
 */
#[TableName('subscription_cancellation_attempts')]
#[Key(['userId'])]
#[Key(['subscriptionId'])]
#[Key(['createdTs'])]
class CancellationAttempt implements Persistence
{
    use MySQL;
    use PersistentModel;

    public const string OFFER_SWITCH_TO_ANNUAL_10PCT = 'switch_to_annual_10pct';
    public const string OFFER_RENEWAL_10PCT = 'renewal_10pct';
    public const string OFFER_NONE_INELIGIBLE = 'none_ineligible';

    public const string OUTCOME_SAVED = 'saved';
    public const string OUTCOME_CANCELLED = 'cancelled';
    public const string OUTCOME_ABANDONED = 'abandoned';

    public const int RETENTION_OFFER_COOLDOWN_SECONDS = 730 * 86400;

    /** @var array<string, string> */
    public static function getReasonOptions(): array
    {
        return [
            'too_expensive' => 'Too expensive',
            'not_using_enough' => 'Not using enough',
            'missing_features' => 'Missing features',
            'technical_problems' => 'Technical problems',
            'season_over' => 'Season over / only need preseason',
            'switching_competitor' => 'Switching to a competitor',
            'other' => 'Other',
        ];
    }

    /** @return array<string, string> */
    public static function getOfferTypeLabels(): array
    {
        return [
            self::OFFER_SWITCH_TO_ANNUAL_10PCT => 'Switch to annual + 10% off',
            self::OFFER_RENEWAL_10PCT => '10% off next renewal',
            self::OFFER_NONE_INELIGIBLE => 'Offer not available',
        ];
    }

    /** @return array<string, string> */
    public static function getOutcomeLabels(): array
    {
        return [
            self::OUTCOME_SAVED => 'Saved',
            self::OUTCOME_CANCELLED => 'Cancelled',
            self::OUTCOME_ABANDONED => 'Abandoned',
        ];
    }

    public int $userId;
    public int $subscriptionId;

    #[Inclusion([
        'too_expensive',
        'not_using_enough',
        'missing_features',
        'technical_problems',
        'season_over',
        'switching_competitor',
        'other',
    ])]
    public string $reasonCode;

    public string $comment = '';
    public string $billingCycleKeyAtAttempt;
    public string $planKeyAtAttempt;

    #[Inclusion([
        self::OFFER_SWITCH_TO_ANNUAL_10PCT,
        self::OFFER_RENEWAL_10PCT,
        self::OFFER_NONE_INELIGIBLE,
    ])]
    public string $offerType;

    public bool $offerAccepted = false;

    #[Inclusion(['', self::OUTCOME_SAVED, self::OUTCOME_CANCELLED, self::OUTCOME_ABANDONED])]
    public string $outcome = '';

    public int $voucherCodeId = 0;
    public int $checkoutTransactionId = 0;
    public int $createdTs;
    public int $completedTs = 0;

    public function isOpen(): bool
    {
        return $this->outcome === '';
    }

    public static function userUsedRetentionOfferWithinCooldown(int $userId): bool
    {
        $sinceTs = Time::getNow() - self::RETENTION_OFFER_COOLDOWN_SECONDS;

        return self::count(new SearchArguments([
            new SearchComparison('`userId`', '=', $userId),
            new SearchComparison('`offerAccepted`', '=', 1),
            new SearchComparison('`completedTs`', '>=', $sinceTs),
        ])) > 0;
    }

    public static function getOpenAttemptForSubscription(int $subscriptionId): ?self
    {
        return self::searchOne(new SearchArguments([
            new SearchComparison('`subscriptionId`', '=', $subscriptionId),
            new SearchComparison('`outcome`', '=', ''),
        ]));
    }
}
