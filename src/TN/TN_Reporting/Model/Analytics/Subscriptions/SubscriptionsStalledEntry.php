<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_subscriptions_stalled_entries')]
class SubscriptionsStalledEntry extends SubscriptionsTypeEntry
{
    public static string $type = 'stalled';
}