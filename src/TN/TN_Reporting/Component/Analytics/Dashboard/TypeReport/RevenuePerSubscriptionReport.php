<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenuePerSubscriptionEntry;

class RevenuePerSubscriptionReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = RevenuePerSubscriptionEntry::class;

    /** @var string  */
    public string $reportKey = 'revenuePerSubscription';

    /** @var bool  */
    public bool $disableTimeUnitSelect = true;
}