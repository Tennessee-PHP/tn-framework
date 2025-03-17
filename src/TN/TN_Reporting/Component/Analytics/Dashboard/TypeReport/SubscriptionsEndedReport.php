<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsEndedEntry;

class SubscriptionsEndedReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsEndedEntry::class;

    /** @var string  */
    public string $reportKey = 'endedSubscriptions';

    /** @var string  */
    public string $chartType = 'bar';
}