<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Component\Analytics\Dashboard\DashboardComponent;

class DashboardSummary extends DashboardComponent
{
    public array $summaryBlocks = [];

    public function prepare(): void
    {
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new RevenueDailySummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new RevenueRecurringSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new SubscriptionsChurnSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new SubscriptionsActiveSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new EmailListConvertKitActiveSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new ExpensesRefundsSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new RevenuePerSubscriptionSummary()
        ];
        $this->summaryBlocks[] = [
            'md-cols' => 6,
            'component' => new SubscriptionsLifetimeValueSummary()
        ];
        foreach ($this->summaryBlocks as $i => $block) {
            $block['component']->prepare();
        }
    }
}