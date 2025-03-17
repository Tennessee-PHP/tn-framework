<?php

namespace TN\TN_Reporting\Model\Analytics\Revenue;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_revenue_recurring_entries')]
class RevenueRecurringEntry extends AnalyticsEntry
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
    public float $monthlyRecurringRevenue = 0;

    /** @var float */
    public float $annualRecurringRevenue = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating recurring revenue report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $data = [];

        $endTs = strtotime(date('Y-m-d 23:59:59', $this->dayTs));

        // use datetime object to subtract a month
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->dayTs);
        $datetime->modify('-1 month');
        $monthlyStartTs = $datetime->getTimestamp();

        // do the same for annual
        $datetime = new \DateTime();
        $datetime->setTimestamp($this->dayTs);
        $datetime->modify('-1 year');
        $annualStartTs = $datetime->getTimestamp();

        $filters = [
            'recurring' => true
        ];

        if (!empty($this->planKey)) {
            $filters['planKey'] = $this->planKey;
        }
        if (!empty($this->billingCycleKey)) {
            $filters['billingCycleKey'] = $this->billingCycleKey;
        }

        if (empty($this->gatewayKey)) {
            $transactionClass = Transaction::class;
            $func = 'getAllCounts';
        } else {
            $transactionClass = Gateway::getInstanceByKey($this->gatewayKey)->transactionClass;
            $func = 'count';
        }

        $data['monthlyRecurringRevenue'] = $transactionClass::$func($monthlyStartTs, $endTs, $filters)['total'];
        $data['annualRecurringRevenue'] = $transactionClass::$func($annualStartTs, $endTs, $filters)['total'];
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

    public static function getValuesFromDayReports(array $dayReports): array
    {
        // for each, we want to show total churn, for each of the reasons
        $totals = [
            'monthly' => 0,
            'annual' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $totals['monthly'] += $dayReport->monthlyRecurringRevenue;
            $totals['annual'] += $dayReport->annualRecurringRevenue;
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
            new AnalyticsDataSeriesColumn('annual', 'Annual Run Rate', array_merge($options, ['emphasize' => true, 'chart' => true])),
            new AnalyticsDataSeriesColumn('monthly', 'Monthly Run Rate', $options)
        ];
    }
}