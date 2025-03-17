<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsActiveEntry;

class SubscriptionsActiveSummary extends TypeSummary
{
    public string $analyticsEntryClass = SubscriptionsActiveEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'active';

    public ?string $title = 'Active Subscriptions';

    public string $compareMethod = 'last';

    public string $compareKey = 'activeSubscriptions';

    public array $dialDisplayOptions = [
        'prefix' => '',
        'decimals' => 0
    ];
}