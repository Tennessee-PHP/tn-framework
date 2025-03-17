<?php

namespace TN\TN_Reporting\Model\Analytics\Expenses;

use TN\TN_Billing\Model\Gateway\Gateway;
use TN\TN_Billing\Model\Subscription\BillingCycle\BillingCycle;
use TN\TN_Billing\Model\Subscription\Plan\Plan;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_expenses_fees_entries')]
class ExpensesFeesEntry extends AnalyticsEntry
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
    public float $fees = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating expenses fee report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->gatewayKey, $this->planKey, $this->billingCycleKey]) . PHP_EOL;
        $filters = [];

        if (!empty($this->planKey)) {
            $filters['planKey'] = $this->planKey;
        }
        if (!empty($this->billingCycleKey)) {
            $filters['billingCycleKey'] = $this->billingCycleKey;
        }

        if (empty($this->gatewayKey)) {
            $transactionClass = Transaction::class;
            $func = 'getAllTransactions';
        } else {
            $transactionClass = Gateway::getInstanceByKey($this->gatewayKey)->transactionClass;
            $func = 'getTransactions';
        }

        $fees = 0.0;
        foreach($transactionClass::$func($this->dayTs, strtotime(date('Y-m-d 23:59:59', $this->dayTs)), $filters) as $transaction) {
            $fees += $transaction->getFee();
        }
        $this->update([
            'fees' => $fees
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
        $total = 0;
        foreach ($dayReports as $dayReport) {
            $total += $dayReport->fees;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'fees' => $total
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 2,
            'prefix' => '$'
        ];

        return [
            new AnalyticsDataSeriesColumn('fees', 'Gateway Fees', array_merge($options, ['emphasize' => true, 'chart' => true]))
        ];
    }
}