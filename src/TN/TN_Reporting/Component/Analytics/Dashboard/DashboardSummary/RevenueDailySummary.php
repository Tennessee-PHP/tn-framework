<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenueDailyEntry;

class RevenueDailySummary extends TypeSummary
{
    public string $analyticsEntryClass = RevenueDailyEntry::class;

    public string $chartType = 'bar';

    public string $reportKey = 'dailyRevenue';

    public ?string $title = 'Daily Revenue (Last 30 Days)';

    public string $compareMethod = 'total';

    public string $compareKey = 'revenue';
}