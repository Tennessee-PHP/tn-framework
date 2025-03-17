<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenueRecurringEntry;

class RevenueRecurringSummary extends TypeSummary
{
    public string $analyticsEntryClass = RevenueRecurringEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'recurringRevenue';

    public ?string $title = 'Annual Run Rate';

    public string $compareMethod = 'last';

    public string $compareKey = 'annualRecurringRevenue';
}