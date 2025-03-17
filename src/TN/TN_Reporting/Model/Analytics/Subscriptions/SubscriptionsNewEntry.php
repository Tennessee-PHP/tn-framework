<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_subscriptions_new_entries')]
class SubscriptionsNewEntry extends SubscriptionsTypeEntry
{
    public static string $type = 'new';
}