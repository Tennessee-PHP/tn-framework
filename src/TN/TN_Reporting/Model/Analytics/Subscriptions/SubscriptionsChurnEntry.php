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

#[TableName('analytics_subscriptions_churn_entries')]
class SubscriptionsChurnEntry extends AnalyticsEntry
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
    public int $churnDays = 30;

    /** @var int */
    public int $churnStartCount = 0;

    /** @var int */
    public int $endedCount = 0;

    /** @var int */
    public int $endedUserCancelledCount = 0;

    /** @var int */
    public int $endedPaymentFailedCount = 0;

    /** @var int */
    public int $endedRefundedCount = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating churn report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;

        // set $churnStartTs
        $churnStartTs = strtotime("-{$this->churnDays} days", $this->dayTs);
        $churnEndTs = strtotime("+ 1 day", $this->dayTs); // want to go up until midnight of the following day
        $data = [];

        // churnStartCount
        $data['churnStartCount'] = Subscription::countActive(
            $churnStartTs,
            $this->planKey ?? '',
            $this->billingCycleKey ?? '',
            $this->gatewayKey ?? ''
        );

        // endedCount (for churn, less upgraded subscriptions)
        $data['endedCount'] = Subscription::countAndTotalByType(
                'ended',
                $churnStartTs,
                $churnEndTs,
                $this->planKey ?? '',
                $this->billingCycleKey ?? '',
                $this->gatewayKey ?? ''
            )->count - Subscription::countAndTotalByType(
                'ended',
                $churnStartTs,
                $churnEndTs,
                $this->planKey ?? '',
                $this->billingCycleKey ?? '',
                $this->gatewayKey ?? '',
                'upgraded'
            )->count;

        // endedUserCancelledCount
        $data['endedUserCancelledCount'] = Subscription::countAndTotalByType(
            'ended',
            $churnStartTs,
            $churnEndTs,
            $this->planKey ?? '',
            $this->billingCycleKey ?? '',
            $this->gatewayKey ?? '',
            'user-cancelled'
        )->count;

        // endedPaymentFailedCount
        $data['endedPaymentFailedCount'] = Subscription::countAndTotalByType(
            'ended',
            $churnStartTs,
            $churnEndTs,
            $this->planKey ?? '',
            $this->billingCycleKey ?? '',
            $this->gatewayKey ?? '',
            'payment-failed'
        )->count;

        // endedRefundedCount
        $data['endedRefundedCount'] = Subscription::countAndTotalByType(
            'ended',
            $churnStartTs,
            $churnEndTs,
            $this->planKey ?? '',
            $this->billingCycleKey ?? '',
            $this->gatewayKey ?? '',
            'refunded'
        )->count;

        // update with all the data
        $this->update($data);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['gatewayKey'] = [null];
        foreach (Gateway::getInstances() as $gateway) {
            if ($gateway->key !== 'free') {
                $values['gatewayKey'][] = $gateway->key;
            }
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

    /**
     * @param int $churnStartCount
     * @param int $endedCount
     * @return float
     */
    public static function calculateChurn(int $churnStartCount, int $endedCount): float
    {
        return $churnStartCount > 0 ? (($endedCount / $churnStartCount) * 100) : 0.0;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        // for each, we want to show total churn, for each of the reasons
        $churnTotals = [
            'total' => 0,
            'user-cancelled' => 0,
            'payment-failed' => 0,
            'refunded' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $churnTotals['total'] += self::calculateChurn($dayReport->churnStartCount, $dayReport->endedCount);
            $churnTotals['user-cancelled'] += self::calculateChurn($dayReport->churnStartCount, $dayReport->endedUserCancelledCount);
            $churnTotals['payment-failed'] += self::calculateChurn($dayReport->churnStartCount, $dayReport->endedPaymentFailedCount);
            $churnTotals['refunded'] += self::calculateChurn($dayReport->churnStartCount, $dayReport->endedRefundedCount);
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'total-churn' => $churnTotals['total'] > 0 ? $churnTotals['total'] / count($dayReports) : 0.0,
            'user-cancelled-churn' => $churnTotals['user-cancelled'] > 0 ? $churnTotals['user-cancelled'] / count($dayReports) : 0.0,
            'payment-failed-churn' => $churnTotals['payment-failed'] > 0 ? $churnTotals['payment-failed'] / count($dayReports) : 0.0,
            'refunded-churn' => $churnTotals['refunded'] > 0 ? $churnTotals['refunded'] / count($dayReports) : 0.0
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $churnOptions = [
            'suffix' => '%',
            'decimals' => 2
        ];

        return [
            new AnalyticsDataSeriesColumn('total-churn', 'Churn', array_merge($churnOptions, ['emphasize' => true, 'chart' => true])),
            new AnalyticsDataSeriesColumn('user-cancelled-churn', 'Cancellations', $churnOptions),
            new AnalyticsDataSeriesColumn('payment-failed-churn', 'Failed Payments', $churnOptions),
            new AnalyticsDataSeriesColumn('refunded-churn', 'Refunds', $churnOptions)
        ];
    }
}