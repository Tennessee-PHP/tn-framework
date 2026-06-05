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

/**
 * Append-only log of user cancellation and auto-renew restore events (by event date, not access end).
 */
#[TableName('subscription_cancel_events')]
#[Key(['subscriptionId', 'eventType', 'eventTs'], unique: true)]
#[Key(['eventTs', 'gatewayKey', 'planKey', 'billingCycleKey'])]
class SubscriptionCancelEvent implements Persistence
{
    use MySQL;
    use PersistentModel;

    public const string EVENT_TYPE_CANCEL = 'cancel';
    public const string EVENT_TYPE_UNCANCEL = 'uncancel';

    public int $userId;
    public int $subscriptionId;

    #[Inclusion([self::EVENT_TYPE_CANCEL, self::EVENT_TYPE_UNCANCEL])]
    public string $eventType;

    public int $eventTs;
    public string $gatewayKey = '';
    public string $planKey = '';
    public string $billingCycleKey = '';

    /**
     * Count events of $eventType whose eventTs falls on the calendar day of $dayTs.
     */
    public static function countForDay(
        string $eventType,
        int $dayTs,
        string $gatewayKey = '',
        string $planKey = '',
        string $billingCycleKey = ''
    ): int {
        $startTs = strtotime(date('Y-m-d 00:00:00', $dayTs));
        $endTs = strtotime('+1 day', $startTs);

        $conditions = [
            new SearchComparison('`eventType`', '=', $eventType),
            new SearchComparison('`eventTs`', '>=', $startTs),
            new SearchComparison('`eventTs`', '<', $endTs),
        ];

        if ($gatewayKey !== '') {
            $conditions[] = new SearchComparison('`gatewayKey`', '=', $gatewayKey);
        }

        if ($planKey !== '') {
            $conditions[] = new SearchComparison('`planKey`', '=', $planKey);
        }

        if ($billingCycleKey !== '') {
            $conditions[] = new SearchComparison('`billingCycleKey`', '=', $billingCycleKey);
        }

        return self::count(new SearchArguments(conditions: $conditions));
    }

    public static function existsForSubscription(
        int $subscriptionId,
        string $eventType,
        int $eventTs
    ): bool {
        return self::count(new SearchArguments([
            new SearchComparison('`subscriptionId`', '=', $subscriptionId),
            new SearchComparison('`eventType`', '=', $eventType),
            new SearchComparison('`eventTs`', '=', $eventTs),
        ])) > 0;
    }
}
