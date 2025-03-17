<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsNewEntry;

class SubscriptionsNewReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsNewEntry::class;

    /** @var string  */
    public string $reportKey = 'newSubscriptions';

    /** @var string  */
    public string $chartType = 'bar';
}