<?php

namespace TN\TN_Reporting\Model\Analytics\Revenue;

use TN\TN_Billing\Model\Refund\Refund;
use TN\TN_Billing\Model\Subscription\Subscription;
use TN\TN_Billing\Model\Transaction\Transaction;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;
use TN\TN_Reporting\Model\Campaign\Campaign;

#[TableName('analytics_campaign_daily_entries')]
class RevenueDailyEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = ['campaign'];

    /** @var int|null */
    public ?int $campaignId = null;

    /** @var int */
    public int $subscriptions = 0;

    /** @var float */
    public float $revenue = 0;


    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating daily campaign report for ' . date('Y-m-d', $this->dayTs) . implode(', ', [$this->campaignId]) . PHP_EOL;
        $data = [];

        $endTs = strtotime(date('Y-m-d 23:59:59', $this->dayTs));

        $filters = [];
        if (!empty($this->campaignId)) {
            $filters['campaignId'] = $this->campaignId;
        }

        $result = Subscription::countAndTotalByType('new', $this->dayTs, strtotime('+1 day', $this->dayTs), '', '', '', null, $this->campaignId);

        $data['subscriptions'] = $result['count'];
        $data['revenue'] = $result['total'];

        $this->update($data);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        $values = [];

        $campaigns = Campaign::search(new SearchArguments());
        foreach ($campaigns as $campaign) {
            $values['campaignId'][] = $campaign->id;
        }

        return $values;
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        // for each, we want to show total churn, for each of the reasons
        $totals = [
            'subscriptions' => 0,
            'revenue' => 0
        ];

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $totals['subscriptions'] += $dayReport->subscriptions;
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
            new AnalyticsDataSeriesColumn('subscriptions', 'Subscriptions', [
                'decimals' => 0
            ])
        ];
    }
}
