<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsUpgradeEntry;

class SubscriptionsUpgradeReport extends TypeReport
{
    /** @var string */
    public string $analyticsEntryClassName = SubscriptionsUpgradeEntry::class;

    /** @var string */
    public string $reportKey = 'upgradeSubscriptions';

    /** @var string */
    public string $chartType = 'bar';
}
