<?php

namespace TN\TN_Reporting\Model\Analytics\Expenses;

use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonArgument;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparisonOperator;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_expenses_refunds_entries')]
class ExpensesRefundsEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['refundReason'];

    /** @var string|null */
    public ?string $refundReasonKey = null;

    /** @var float */
    public float $refundCount = 0;

    public float $refundTotal = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating expenses refund report for ' . date('Y-m-d', $this->dayTs) . $this->refundReasonKey . PHP_EOL;

        $conditions = [
            new SearchComparison('`ts`', '>=', $this->dayTs),
            new SearchComparison('`ts`', '<=', strtotime(date('Y-m-d 23:59:59', $this->dayTs)))
        ];

        if ($this->refundReasonKey) {
            $conditions[] = new SearchComparison('`reason`', '=', $this->refundReasonKey);
        }

        $result = Refund::countAndTotal(new SearchArguments(
            conditions: $conditions
        ), 'amount');

        $this->update([
            'refundTotal' => $result->total,
            'refundCount' => $result->count
        ]);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];
        $values['refundReasonKey'] = [''];

        $refundClass = Stack::resolveClassName(Refund::class);
        foreach ($refundClass::getReasonOptions() as $key => $label) {
            $values['refundReasonKey'][] = $key;
        }
        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $totals = [
            'refundTotal' => 0,
            'refundCount' => 0
        ];
        foreach ($dayReports as $dayReport) {
            $totals['refundTotal'] += $dayReport->refundTotal;
            $totals['refundCount'] += $dayReport->refundCount;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return $totals;
    }

    public static function getBaseDataSeriesColumns(): array
    {
        return [
            new AnalyticsDataSeriesColumn('refundTotal', 'Refund Total $', ['emphasize' => true, 'chart' => true, 'prefix' => '$', 'decimals' => 2]),
            new AnalyticsDataSeriesColumn('refundCount', '# of Refunds', ['chart' => false, 'decimals' => 0])
        ];
    }
}
