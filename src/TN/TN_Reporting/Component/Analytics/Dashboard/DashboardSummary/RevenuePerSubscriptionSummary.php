<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Revenue\RevenuePerSubscriptionEntry;

class RevenuePerSubscriptionSummary extends TypeSummary
{
    public string $analyticsEntryClass = RevenuePerSubscriptionEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'revenuePerSubscription';

    public ?string $title = 'Annual Revenue Per Subscription';

    public string $compareMethod = 'last';

    public string $compareKey = 'annualRevenuePerSubscription';

    public array $dialDisplayOptions = [
        'prefix' => '$',
        'decimals' => 2
    ];
}