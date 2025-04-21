<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

abstract class SubscriptionsTypeEntry extends AnalyticsEntry
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
    public float $count = 0;

    public static string $type;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription ' . get_called_class()::$type . ' report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $this->update([
            'count' => Subscription::countAndTotalByType(get_called_class()::$type, $this->dayTs, strtotime('+1 day', $this->dayTs), $this->planKey ?? '', $this->billingCycleKey ?? '', $this->gatewayKey ?? '')['count']
        ]);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [null];
        foreach (Gateway::getInstances() as $gateway) {
            $values['gatewayKey'][] = $gateway->key;
        }
        $values['planKey'] = [null];
        foreach (Plan::getInstances() as $plan) {
            if ($plan->paid) {
                $values['planKey'][] = $plan->key;
            }
        }
        $values['billingCycleKey'] = [null];
        foreach (BillingCycle::getInstances() as $billingCycle) {
            $values['billingCycleKey'][] = $billingCycle->key;
        }
        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $count = 0;

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $count += $dayReport->count;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'count' => $count
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0
        ];

        return [
            new AnalyticsDataSeriesColumn('count', ucfirst(get_called_class()::$type) . ' Subscriptions', array_merge($options, ['emphasize' => true, 'chart' => true]))
        ];
    }
}