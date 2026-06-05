<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_subscriptions_new_entries')]
class SubscriptionsNewEntry extends SubscriptionsTypeEntry
{
    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle', 'customerType'];

    /** @var string|null */
    public ?string $customerTypeKey = null;

    /** @var float */
    public float $revenue = 0;

    public static string $type = 'new';

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription ' . static::$type . ' report for ' . date('Y-m-d', $this->dayTs)
            . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey, $this->customerTypeKey]) . PHP_EOL;

        $endTs = strtotime('+1 day', $this->dayTs);
        $customerTypeKey = $this->customerTypeKey ?? '';

        if ($customerTypeKey === '') {
            $result = Subscription::countAndTotalByType(
                static::$type,
                $this->dayTs,
                $endTs,
                $this->planKey ?? '',
                $this->billingCycleKey ?? '',
                $this->gatewayKey ?? ''
            );
        } else {
            $result = Subscription::countAndTotalNewByCustomerType(
                $customerTypeKey,
                $this->dayTs,
                $endTs,
                $this->planKey ?? '',
                $this->billingCycleKey ?? '',
                $this->gatewayKey ?? ''
            );
        }

        $this->update([
            'count' => $result->count,
            'revenue' => $result->total,
        ]);
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
        $values['customerTypeKey'] = [
            '',
            Subscription::CUSTOMER_TYPE_BRAND_NEW,
            Subscription::CUSTOMER_TYPE_PREVIOUSLY_SUBSCRIBED,
        ];

        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $count = 0;
        $revenue = 0.0;

        foreach ($dayReports as $dayReport) {
            $count += $dayReport->count;
            $revenue += $dayReport->revenue;
        }

        return [
            'count' => $count,
            'revenue' => $revenue,
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $countOptions = [
            'decimals' => 0,
        ];
        $revenueOptions = [
            'decimals' => 2,
            'prefix' => '$',
        ];

        return [
            new AnalyticsDataSeriesColumn(
                'count',
                'New Subscriptions',
                array_merge($countOptions, ['emphasize' => true, 'chart' => true])
            ),
            new AnalyticsDataSeriesColumn(
                'revenue',
                'New Revenue',
                $revenueOptions
            ),
        ];
    }
}
