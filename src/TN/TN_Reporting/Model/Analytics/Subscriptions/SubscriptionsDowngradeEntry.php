<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_subscriptions_downgrade_entries')]
class SubscriptionsDowngradeEntry extends SubscriptionsPlanChangeEntry
{
    public static function getChangeType(): string
    {
        return PlanChange::CHANGE_TYPE_DOWNGRADE;
    }

    public static function getCountLabel(): string
    {
        return 'Downgrade Subscriptions';
    }
}
