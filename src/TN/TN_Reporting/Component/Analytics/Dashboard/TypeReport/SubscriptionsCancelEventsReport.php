<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsCancelEventsEntry;

class SubscriptionsCancelEventsReport extends TypeReport
{
    public string $analyticsEntryClassName = SubscriptionsCancelEventsEntry::class;

    public string $reportKey = 'subscriptionCancellations';

    public string $chartType = 'bar';
}
