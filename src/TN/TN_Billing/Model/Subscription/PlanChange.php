<?php

namespace TN\TN_Billing\Model\Subscription;

use TN\TN_Core\Attribute\Constraints\Inclusion;
use TN\TN_Core\Attribute\MySQL\Key;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;

/**
 * Event log for subscription plan upgrades and scheduled/applied downgrades.
 */
#[TableName('subscription_plan_changes')]
#[Key(['subscriptionId'])]
#[Key(['subscriptionId', 'scheduledDowngradeMarker'], unique: true)]
class PlanChange implements Persistence
{
    use MySQL;
    use PersistentModel;

    public const string CHANGE_TYPE_UPGRADE = 'upgrade';
    public const string CHANGE_TYPE_DOWNGRADE = 'downgrade';

    public const string STATUS_SCHEDULED = 'scheduled';
    public const string STATUS_APPLIED = 'applied';

    /** Set to 1 only while a downgrade is scheduled; NULL for all other rows (enforces one scheduled downgrade per subscription). */
    public const int SCHEDULED_DOWNGRADE_MARKER = 1;

    public int $userId;
    public int $subscriptionId;

    #[Inclusion([self::CHANGE_TYPE_UPGRADE, self::CHANGE_TYPE_DOWNGRADE])]
    public string $changeType;

    #[Inclusion([self::STATUS_SCHEDULED, self::STATUS_APPLIED])]
    public string $status;

    public string $fromPlanKey;
    public string $toPlanKey;
    public string $fromBillingCycleKey;
    public string $toBillingCycleKey;

    public int $effectiveTs;
    public int $recordedTs;
    public int $transactionId = 0;

    /** @var int|null 1 when {@see self::CHANGE_TYPE_DOWNGRADE} and {@see self::STATUS_SCHEDULED}; otherwise unset */
    public ?int $scheduledDowngradeMarker = null;

    /**
     * Count applied plan changes whose effective time falls on the calendar day of $dayTs.
     * Optional filters: subscription gateway (join), destination plan, destination billing cycle.
     */
    public static function countAppliedForDay(
        string $changeType,
        int $dayTs,
        string $gatewayKey = '',
        string $planKey = '',
        string $billingCycleKey = ''
    ): int {
        $startTs = strtotime(date('Y-m-d 00:00:00', $dayTs));
        $endTs = strtotime('+1 day', $startTs);

        $conditions = [
            new SearchComparison('`changeType`', '=', $changeType),
            new SearchComparison('`status`', '=', self::STATUS_APPLIED),
            new SearchComparison('`effectiveTs`', '>=', $startTs),
            new SearchComparison('`effectiveTs`', '<', $endTs),
        ];

        if ($planKey !== '') {
            $conditions[] = new SearchComparison('`toPlanKey`', '=', $planKey);
        }

        if ($billingCycleKey !== '') {
            $conditions[] = new SearchComparison('`toBillingCycleKey`', '=', $billingCycleKey);
        }

        if ($gatewayKey !== '') {
            $conditions[] = new SearchComparison(
                new SearchComparisonArgument(property: 'subscriptionId', class: self::class),
                '=',
                new SearchComparisonArgument(property: 'id', class: Subscription::class)
            );
            $conditions[] = new SearchComparison(
                new SearchComparisonArgument(property: 'gatewayKey', class: Subscription::class),
                '=',
                $gatewayKey
            );
        }

        return self::count(new SearchArguments(conditions: $conditions));
    }
}
