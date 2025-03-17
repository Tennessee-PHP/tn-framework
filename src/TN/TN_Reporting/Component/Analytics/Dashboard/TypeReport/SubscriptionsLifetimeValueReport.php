<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsLifetimeValueEntry;

class SubscriptionsLifetimeValueReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = SubscriptionsLifetimeValueEntry::class;

    /** @var string  */
    public string $reportKey = 'subscriptionLifetimeValue';
}