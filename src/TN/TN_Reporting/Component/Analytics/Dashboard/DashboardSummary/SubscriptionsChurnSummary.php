<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsChurnEntry;

class SubscriptionsChurnSummary extends TypeSummary
{
    public string $analyticsEntryClass = SubscriptionsChurnEntry::class;

    public string $chartType = 'line';

    public string $reportKey = 'churn';

    public ?string $title = 'Churn Rate';

    public string $compareMethod = 'last';

    public string $compareKey = 'churnStartCount';

    public array $dialDisplayOptions = [
        'prefix' => '',
        'suffix' => '%',
        'decimals' => 2,
        'absoluteMath' => true,
        'invertGoal' => true
    ];

    protected function getDialTotals(): array
    {
        $totals = [
            'now' => 0,
            'year' => 0,
            'season' => 0
        ];

        $compareKey = $this->compareKey;

        foreach (array_reverse($this->dataSeries->entries) as $entry) {
            foreach ($entry->dayReportsByPrefix as $prefix => $dayReports) {
                $key = match ($prefix) {
                    '1-year' => 'year',
                    '1-season' => 'season',
                    default => 'now'
                };
                foreach ($dayReports as $dayReport) {
                    if (isset($dayReport->$compareKey)) {
                        $totals[$key] = SubscriptionsChurnEntry::calculateChurn($dayReport->churnStartCount, $dayReport->endedCount);
                    }
                }
            }
        }

        return $totals;
    }
}