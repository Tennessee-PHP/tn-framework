<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsStalledEntry;

class SubscriptionsStalledReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsStalledEntry::class;

    /** @var string  */
    public string $reportKey = 'stalledSubscriptions';

    /** @var string  */
    public string $chartType = 'bar';
}