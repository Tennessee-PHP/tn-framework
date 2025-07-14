<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_subscriptions_active_entries')]
class SubscriptionsActiveEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle'];

    /** @var string|null */
    public ?string $gatewayKey = null;

    /** @var string|null */
    public ?string $planKey = null;

    /** @var string|null */
    public ?string $billingCycleKey = null;

    /** @var int */
    public int $activeSubscriptions = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating active subscriptions report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $data = [];
        $data['activeSubscriptions'] = Subscription::countActive(
            $this->dayTs,
            $this->planKey ?? '',
            $this->billingCycleKey ?? '',
            $this->gatewayKey ?? ''
        );
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
        $total = 0;

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $total += $dayReport->activeSubscriptions;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'active-subscriptions' => $total > 0 ? $total / count($dayReports) : 0.0
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        return [
            new AnalyticsDataSeriesColumn('active-subscriptions', 'Active Subscriptions', [
                'emphasize' => true,
                'decimals' => 0,
                'chart' => true
            ])
        ];
    }
}
