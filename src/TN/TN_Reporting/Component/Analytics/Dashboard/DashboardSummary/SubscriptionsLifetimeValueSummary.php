<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsLifetimeValueEntry;

class SubscriptionsLifetimeValueSummary extends TypeSummary
{
    public string $analyticsEntryClass = SubscriptionsLifetimeValueEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'subscriptionLifetimeValue';

    public ?string $title = 'Lifetime Value of a Subscription (Estimate)';

    public string $compareMethod = 'last';

    public string $compareKey = 'lifetimeValue';

    public array $dialDisplayOptions = [
        'prefix' => '$',
        'decimals' => 2
    ];
}