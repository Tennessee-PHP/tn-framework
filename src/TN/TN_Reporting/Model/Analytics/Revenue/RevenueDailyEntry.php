<?php

namespace TN\TN_Reporting\Model\Analytics\Revenue;

use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_revenue_daily_entries')]
class RevenueDailyEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['productType'];

    /** @var string|null */
    public ?string $productTypeKey = null;

    /** @var float */
    public float $turnover = 0;

    /** @var float */
    public float $refunds = 0;

    /** @var float */
    public float $revenue = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating daily revenue report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->productTypeKey]) . PHP_EOL;
        $data = [];

        $endTs = strtotime(date('Y-m-d 23:59:59', $this->dayTs));

        $filters = [];
        if (!empty($this->productTypeKey)) {
            $filters['productTypeKey'] = $this->productTypeKey;
        }

        $conditions = [
            new SearchComparison('`ts`', '>=', $this->dayTs),
            new SearchComparison('`ts`', '<=', $endTs)
        ];

        if (!empty($this->productTypeKey)) {
            $conditions[] = new SearchComparison(
                $this->productTypeKey === 'subscription' ? '`subscriptionId`' : '`giftSubscriptionId`',
                '>',
                0
            );
        }


        $data['turnover'] = Transaction::getAllCounts(new SearchArguments(conditions: $conditions))->total;

        $refunds = Refund::search(new SearchArguments(conditions: [
            new SearchComparison('`ts`', '>=', $this->dayTs),
            new SearchComparison('`ts`', '<=', $endTs)
        ]));

        $refundTotal = 0;
        foreach ($refunds as $refund) {
            $resolvedTransactionClass = Stack::resolveClassName($refund->transactionClass);
            if (!$resolvedTransactionClass) {
                continue;
            }
            $transaction = $resolvedTransactionClass::readFromId($refund->transactionId);
            if (!$transaction) {
                continue;
            }
            if ($this->productTypeKey) {
                if ($this->productTypeKey === 'subscription' && $transaction->subscriptionId === 0) {
                    continue;
                }
                if ($this->productTypeKey === 'giftSubscription' && $transaction->giftSubscriptionId === 0) {
                    continue;
                }
            }
            $refundTotal += $refund->amount;
        }

        $data['refunds'] = $refundTotal;
        $data['revenue'] = $data['turnover'] - $data['refunds'];

        $this->update($data);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['productTypeKey'] = [null, 'subscription', 'giftSubscription'];
        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        // for each, we want to show total churn, for each of the reasons
        $totals = [
            'turnover' => 0,
            'refunds' => 0,
            'revenue' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $totals['turnover'] += $dayReport->turnover;
            $totals['refunds'] += $dayReport->refunds;
            $totals['revenue'] += $dayReport->revenue;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return $totals;
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 2,
            'prefix' => '$'
        ];

        return [
            new AnalyticsDataSeriesColumn('revenue', 'Revenue', array_merge($options, ['emphasize' => true, 'chart' => true])),
            new AnalyticsDataSeriesColumn('turnover', 'Turnover', array_merge($options)),
            new AnalyticsDataSeriesColumn('refunds', 'Refunds', array_merge($options)),
        ];
    }
}
