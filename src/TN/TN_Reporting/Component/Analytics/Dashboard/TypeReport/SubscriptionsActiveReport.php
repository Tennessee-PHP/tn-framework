<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsActiveEntry;

class SubscriptionsActiveReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsActiveEntry::class;

    /** @var string  */
    public string $reportKey = 'active';
}