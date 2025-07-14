<?php

namespace TN\TN_Reporting\Model\Analytics\Revenue;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;
use TN\TN_Reporting\Model\Analytics\Subscriptions\SubscriptionsActiveEntry;

#[TableName('analytics_revenue_per_subscription_entries')]
class RevenuePerSubscriptionEntry extends AnalyticsEntry
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
    public float $monthlyRevenuePerSubscription = 0;

    /** @var float */
    public float $annualRevenuePerSubscription = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating revenue per user report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $data = [];

        // get the day report for the recurring revenue
        $dayRecurringRevenueReport = RevenueRecurringEntry::searchByProperties([
            'dayTs' => $this->dayTs,
            'gatewayKey' => $this->gatewayKey,
            'planKey' => $this->planKey,
            'billingCycleKey' => $this->billingCycleKey
        ]);
        $dayRecurringRevenueReport = $dayRecurringRevenueReport[0] ?? null;
        if (!$dayRecurringRevenueReport) {
            echo 'no day recurring revenue report' . PHP_EOL;
            return;
        }

        // get the day report for the active subscribers
        $dayActiveReport = SubscriptionsActiveEntry::searchByProperties([
            'dayTs' => $this->dayTs,
            'gatewayKey' => $this->gatewayKey,
            'planKey' => $this->planKey,
            'billingCycleKey' => $this->billingCycleKey
        ]);
        $dayActiveReport = $dayActiveReport[0] ?? null;
        if (!$dayActiveReport) {
            echo 'no day active  report' . PHP_EOL;
            return;
        }

        if ($dayActiveReport->activeSubscriptions > 0) {
            $data['monthlyRevenuePerSubscription'] = $dayRecurringRevenueReport->monthlyRecurringRevenue / $dayActiveReport->activeSubscriptions;
            $data['annualRevenuePerSubscription'] = $dayRecurringRevenueReport->annualRecurringRevenue / $dayActiveReport->activeSubscriptions;
        } else {
            $data['monthlyRevenuePerSubscription'] = 0;
            $data['annualRevenuePerSubscription'] = 0;
        }

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
        // for each, we want to show total churn, for each of the reasons
        $totals = [
            'monthly' => 0,
            'annual' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $totals['monthly'] += $dayReport->monthlyRevenuePerSubscription;
            $totals['annual'] += $dayReport->annualRevenuePerSubscription;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'monthly' => $totals['monthly'] > 0 ? $totals['monthly'] / count($dayReports) : 0.0,
            'annual' => $totals['annual'] > 0 ? $totals['annual'] / count($dayReports) : 0.0
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 2,
            'prefix' => '$'
        ];

        return [
            new AnalyticsDataSeriesColumn('annual', 'Annual Revenue Per Subscription', array_merge($options, ['emphasize' => true, 'chart' => true])),
            new AnalyticsDataSeriesColumn('monthly', 'Monthly Revenue Per Subscription', $options)
        ];
    }
}
