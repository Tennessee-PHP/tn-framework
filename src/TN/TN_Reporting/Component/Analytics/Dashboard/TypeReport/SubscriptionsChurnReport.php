<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsChurnEntry;

class SubscriptionsChurnReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsChurnEntry::class;

    /** @var string  */
    public string $reportKey = 'churn';
}