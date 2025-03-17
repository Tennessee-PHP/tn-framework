<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsRenewalEntry;

class SubscriptionsRenewalReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsRenewalEntry::class;

    /** @var string  */
    public string $reportKey = 'renewalSubscriptions';

    /** @var string  */
    public string $chartType = 'bar';
}