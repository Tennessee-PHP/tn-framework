<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\SubscriptionCancelEvent;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_subscriptions_cancel_events_entries')]
class SubscriptionsCancelEventsEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['gateway', 'plan', 'billingCycle'];

    public ?string $gatewayKey = null;
    public ?string $planKey = null;
    public ?string $billingCycleKey = null;
    public int $cancelledCount = 0;
    public int $uncancelledCount = 0;

    /**
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription cancel events report for ' . date('Y-m-d', $this->dayTs)
            . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;

        $this->update([
            'cancelledCount' => SubscriptionCancelEvent::countForDay(
                SubscriptionCancelEvent::EVENT_TYPE_CANCEL,
                $this->dayTs,
                $this->gatewayKey ?? '',
                $this->planKey ?? '',
                $this->billingCycleKey ?? ''
            ),
            'uncancelledCount' => SubscriptionCancelEvent::countForDay(
                SubscriptionCancelEvent::EVENT_TYPE_UNCANCEL,
                $this->dayTs,
                $this->gatewayKey ?? '',
                $this->planKey ?? '',
                $this->billingCycleKey ?? ''
            ),
        ]);
    }

    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [''];
        foreach (Gateway::getInstances() as $gateway) {
            if ($gateway->key !== 'free') {
                $values['gatewayKey'][] = $gateway->key;
            }
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
        $cancelledCount = 0;
        $uncancelledCount = 0;

        foreach ($dayReports as $dayReport) {
            $cancelledCount += $dayReport->cancelledCount;
            $uncancelledCount += $dayReport->uncancelledCount;
        }

        return [
            'cancelledCount' => $cancelledCount,
            'uncancelledCount' => $uncancelledCount,
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0,
        ];

        return [
            new AnalyticsDataSeriesColumn(
                'cancelledCount',
                'Cancellations',
                array_merge($options, ['emphasize' => true, 'chart' => true])
            ),
            new AnalyticsDataSeriesColumn(
                'uncancelledCount',
                'Uncancellations',
                array_merge($options, ['chart' => true])
            ),
        ];
    }
}
