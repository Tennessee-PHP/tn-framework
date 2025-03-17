<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenueRecurringEntry;

class RevenueRecurringReport extends TypeReport
{

    /** @var string  */
    public string $analyticsEntryClassName = RevenueRecurringEntry::class;

    /** @var string  */
    public string $reportKey = 'recurringRevenue';

    /** @var bool  */
    public bool $disableTimeUnitSelect = true;

    /** @var string  */
    public string $comparisonColumnKey = 'comparison-annual';
}