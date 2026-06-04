<?php

namespace TN\TN_Reporting\Model\Analytics\Subscriptions;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Subscription\PlanChange;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

abstract class SubscriptionsPlanChangeEntry extends AnalyticsEntry
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
    public int $count = 0;

    abstract public static function getChangeType(): string;

    abstract public static function getCountLabel(): string;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating subscription ' . static::getChangeType() . ' report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;

        $this->update([
            'count' => PlanChange::countAppliedForDay(
                static::getChangeType(),
                $this->dayTs,
                $this->gatewayKey ?? '',
                $this->planKey ?? '',
                $this->billingCycleKey ?? ''
            ),
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
        $count = 0;

        foreach ($dayReports as $dayReport) {
            $count += $dayReport->count;
        }

        return [
            'count' => $count,
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0,
        ];

        return [
            new AnalyticsDataSeriesColumn(
                'count',
                static::getCountLabel(),
                array_merge($options, ['emphasize' => true, 'chart' => true])
            ),
        ];
    }
}
