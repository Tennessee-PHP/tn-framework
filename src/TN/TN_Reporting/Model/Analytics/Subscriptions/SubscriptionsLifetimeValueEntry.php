<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;
use TN\TN_Reporting\Model\Analytics\Revenue\RevenuePerSubscriptionEntry;

#[TableName('analytics_subscriptions_lifetime_value_entries')]
class SubscriptionsLifetimeValueEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle'];

    /** @var string|null */
    public ?string $gatewayKey = null;

    /** @var string|null */
    public ?string $planKey = null;

    /** @var string|null */
    public ?string $billingCycleKey = null;

    /** @var float */
    public float $lifetimeValue = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription lifetime value report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $data = [];

        $dayRevenuePerSubscriptionReport = RevenuePerSubscriptionEntry::searchByProperties([
            'dayTs' => $this->dayTs,
            'gatewayKey' => $this->gatewayKey,
            'planKey' => $this->planKey,
            'billingCycleKey' => $this->billingCycleKey
        ]);
        $dayRevenuePerSubscriptionReport = $dayRevenuePerSubscriptionReport[0] ?? null;

        if (!$dayRevenuePerSubscriptionReport) {
            echo 'no day revenue per subscription report' . PHP_EOL;
            return;
        }

        $dayChurnReports = SubscriptionsChurnEntry::search(new SearchArguments([
            new SearchComparison('`dayTs`', '>=', strtotime('-1 year', $this->dayTs)),
            new SearchComparison('`dayTs`', '<=', $this->dayTs),
            new SearchComparison('`gatewayKey`', '=', $this->gatewayKey),
            new SearchComparison('`planKey`', '=', $this->planKey),
            new SearchComparison('`billingCycleKey`', '=', $this->billingCycleKey)
        ]));

        $churnValues = [];
        foreach ($dayChurnReports as $dayChurnReport) {
            $churnValues[] = SubscriptionsChurnEntry::calculateChurn($dayChurnReport->churnStartCount, $dayChurnReport->endedCount) / 100;
        }
        $annualChurnAverage = count($churnValues) > 0 ? array_sum($churnValues) / count($churnValues) : 0;
        $annualChurnAverage = max(0.01, $annualChurnAverage);

        $data['lifetimeValue'] = ($dayRevenuePerSubscriptionReport->annualRevenuePerSubscription / 12) / $annualChurnAverage;

        $this->update($data);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [''];
        foreach (Gateway::getInstances() as $gateway) {
            $values['gatewayKey'][] = $gateway->key;
        }
        $values['planKey'] = [''];
        foreach (Plan::getInstances() as $plan) {
            if ($plan->paid) {
                $values['planKey'][] = $plan->key;
            }
        }
        $values['billingCycleKey'] = [''];
        foreach (BillingCycle::getInstances() as $billingCycle) {
            $values['billingCycleKey'][] = $billingCycle->key;
        }
        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $total = 0;

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $total += $dayReport->lifetimeValue;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'lifetimeValue' => $total > 0 ? $total / count($dayReports) : 0.0
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 2,
            'prefix' => '$'
        ];

        return [
            new AnalyticsDataSeriesColumn('lifetimeValue', 'Lifetime Value', array_merge($options, ['emphasize' => true, 'chart' => true]))
        ];
    }
}
