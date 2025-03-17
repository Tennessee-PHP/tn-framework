<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Core\Attribute\MySQL\TableName;

#[TableName('analytics_subscriptions_renewal_entries')]
class SubscriptionsRenewalEntry extends SubscriptionsTypeEntry
{
    public static string $type = 'renewal';
}