<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsDowngradeEntry;

class SubscriptionsDowngradeReport extends TypeReport
{
    /** @var string */
    public string $analyticsEntryClassName = SubscriptionsDowngradeEntry::class;

    /** @var string */
    public string $reportKey = 'downgradeSubscriptions';

    /** @var string */
    public string $chartType = 'bar';
}
