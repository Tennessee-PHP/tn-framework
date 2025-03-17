<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenueDailyEntry;

class RevenueDailyReport extends TypeReport
{
    /** @var string  */
    public string $analyticsEntryClassName = RevenueDailyEntry::class;

    /** @var string  */
    public string $reportKey = 'dailyRevenue';

    /** @var string  */
    public string $chartType = 'bar';
}