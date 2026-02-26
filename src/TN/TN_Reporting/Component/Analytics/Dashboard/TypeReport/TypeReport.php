<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\TypeReport;

use TN\TN_Billing\Component\Input\Select\BillingCycleSelect\BillingCycleSelect;
use TN\TN_Billing\Component\Input\Select\GatewaySelect\GatewaySelect;
use TN\TN_Core\Component\DataSeries\DataSeriesChart\DataSeriesChart;
use TN\TN_Core\Component\DataSeries\DataSeriesTable\DataSeriesTable;
use TN\TN_Core\Component\Input\DateInput\DateInput;
use TN\TN_Core\Component\Input\Select\PlanSelect\PlanSelect;
use TN\TN_Core\Component\Input\Select\CampaignSelect\CampaignSelect;
use TN\TN_Core\Component\Input\Select\ProductTypeSelect\EndedReasonSelect;
use TN\TN_Core\Component\Input\Select\ProductTypeSelect\ProductTypeSelect;
use TN\TN_Core\Component\Input\Select\ProductTypeSelect\RefundReasonSelect;
use TN\TN_Core\Component\Input\Select\TimeCompareSelect\TimeCompareSelect;
use TN\TN_Core\Component\Input\Select\TimeUnitSelect\TimeUnitSelect;
use TN\TN_Core\Model\DataSeries\DataSeries;
use TN\TN_Core\Model\DataSeries\DataSeriesColumn;
use TN\TN_Core\Model\DataSeries\DataSeriesEntry;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Component\Analytics\Dashboard\DashboardComponent;
use TN\TN_Reporting\Model\Analytics\Campaign\CampaignDailyEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeries;
use TN\TN_Reporting\Model\Campaign\Campaign;

class TypeReport extends DashboardComponent
{

    /** @var TimeUnitSelect */
    public TimeUnitSelect $timeUnitSelect;

    /** @var DateInput */
    public DateInput $dateInput1;

    /** @var DateInput */
    public DateInput $dateInput2;

    /** @var TimeCompareSelect */
    public TimeCompareSelect $timeCompareSelect;

    public array $filterSelects = [];

    /** @var string|null */
    public ?string $breakdown = null;

    /** @var AnalyticsDataSeries */
    public AnalyticsDataSeries $dataSeries;

    /** @var DataSeriesTable */
    public DataSeriesTable $table;

    /** @var DataSeriesChart */
    public DataSeriesChart $chart;

    /** @var string */
    public string $analyticsEntryClassName;

    /** @var string */
    public string $chartType = 'line';

    /** @var bool */
    public bool $disableTimeUnitSelect = false;

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        foreach ($this->analyticsEntryClassName::$filters as $filter) {
            $select = match ($filter) {
                'gateway' => new GatewaySelect(),
                'plan' => new PlanSelect(),
                'billingCycle' => new BillingCycleSelect(),
                'productType' => new ProductTypeSelect(),
                'refundReason' => new RefundReasonSelect(),
                'endedReason' => new EndedReasonSelect(),
                'campaign' => new CampaignSelect(),
            };
            $select->prepare();
            $this->filterSelects[$filter] = $select;
        }

        // let's see if we have a breakdown
        switch ($_REQUEST['breakdown']) {
            case 'gateway':
                if (!in_array('gateway', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'gatewayKey';
                $this->filterSelects['gateway']->selected = $this->filterSelects['gateway']->options[0];
                break;

            case 'campaign':
                // Campaign breakdown is intentionally disabled.
                $this->breakdown = null;
                break;

            case 'plan':
                if (!in_array('plan', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'planKey';
                $this->filterSelects['plan']->selected = $this->filterSelects['plan']->options[0];
                break;

            case 'billingcycle':
                if (!in_array('billingCycle', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'billingCycleKey';
                $this->filterSelects['billingCycle']->selected = $this->filterSelects['billingCycle']->options[0];
                break;
            case 'producttype':
                if (!in_array('productType', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'productTypeKey';
                $this->filterSelects['productType']->selected = $this->filterSelects['productType']->options[0];
                break;
            case 'refundreason':
                if (!in_array('refundReason', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'refundReasonKey';
                $this->filterSelects['refundReason']->selected = $this->filterSelects['refundReason']->options[0];
                break;
            case 'endedreason':
                if (!in_array('endedReason', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'endedReasonKey';
                $this->filterSelects['endedReason']->selected = $this->filterSelects['endedReason']->options[0];
                break;
        };

        $this->timeUnitSelect = new TimeUnitSelect();
        $this->timeUnitSelect->prepare();

        $this->dateInput1 = new DateInput('churn_report_date_1', 'date1', date('Y-m-d', Time::getTodayTs() - (Time::ONE_DAY * 30)));
        $this->dateInput1->prepare();
        $this->dateInput2 = new DateInput('churn_report_date_2', 'date2', date('Y-m-d', Time::getTodayTs()));
        $this->dateInput2->prepare();

        $this->timeCompareSelect = (new TimeCompareSelect());
        $this->timeCompareSelect->prepare();

        $this->prepareDataSeries();
        $this->prepareTable();
        $this->prepareChart();
    }

    /**
     * set the filters for the call to get the data series from the day report
     * @return array
     */
    protected function getDataSeriesFilters(): array
    {
        $filters = [];
        foreach ($this->filterSelects as $filter => $select) {
            $key = is_object($select->selected) ? $select->selected->key : '';
            if ($filter === 'campaign') {
                $filters['campaignId'] = $key;
            } else {
                $filters[$filter . 'Key'] = $key;
            }
        }

        return $filters;
    }

    /**
     * set the data series
     * @return void
     */
    protected function prepareDataSeries(): void
    {
        $timestamps = [strtotime($this->dateInput1->value), strtotime($this->dateInput2->value)];
        $compareTo = empty($this->timeCompareSelect->selected->key) ? null : $this->timeCompareSelect->selected->key;

        $startTs = min($timestamps);
        $endTs = max($timestamps);

        $startTs = Time::moveTsBackToStartOfUnit($this->timeUnitSelect->selected->key, $startTs);
        $endTs = Time::moveTsForwardToEndOfUnit($this->timeUnitSelect->selected->key, $endTs);
        $endTs = min($endTs, Time::getTodayTs());

        $this->dateInput1->value = date('Y-m-d', $startTs);
        $this->dateInput2->value = date('Y-m-d', $endTs);

        $this->dataSeries = new AnalyticsDataSeries(
            $this->analyticsEntryClassName,
            $this->timeUnitSelect->selected->key,
            $startTs,
            $endTs,
            $this->getDataSeriesFilters(),
            $this->breakdown,
            $compareTo
        );
    }

    /**
     * set the table component
     * @return void
     */
    protected function prepareTable(): void
    {
        if ($this->analyticsEntryClassName === CampaignDailyEntry::class) {
            $this->table = new DataSeriesTable($this->getCampaignTableDataSeries());
        } else {
            $this->table = new DataSeriesTable($this->dataSeries);
        }
        $this->table->prepare();
    }

    /**
     * Build a campaign-focused table for each time unit, showing only campaigns
     * with non-zero values in that period.
     */
    protected function getCampaignTableDataSeries(): DataSeries
    {
        $columns = [
            new DataSeriesColumn('date', 'Date'),
            new DataSeriesColumn('campaign', 'Campaign'),
            new DataSeriesColumn('revenue', 'Revenue', ['prefix' => '$', 'decimals' => 2, 'emphasize' => true]),
            new DataSeriesColumn('subscriptions', 'Subscriptions', ['decimals' => 0]),
        ];

        $entries = [];
        $campaignNameById = [];
        foreach ($this->dataSeries->entries as $timeEntry) {
            if (!property_exists($timeEntry, 'dayReports') || !is_array($timeEntry->dayReports)) {
                continue;
            }

            $campaignTotals = [];
            $totalRevenue = 0.0;
            $totalSubscriptions = 0;
            foreach ($timeEntry->dayReports as $dayReport) {
                $campaignId = (int)($dayReport->campaignId ?? 0);
                if ($campaignId <= 0) {
                    continue;
                }
                $revenue = (float)($dayReport->revenue ?? 0);
                $subscriptions = (int)($dayReport->subscriptions ?? 0);
                $totalRevenue += $revenue;
                $totalSubscriptions += $subscriptions;
                if (!isset($campaignTotals[$campaignId])) {
                    if (!isset($campaignNameById[$campaignId])) {
                        $campaign = Campaign::readFromId($campaignId);
                        $campaignNameById[$campaignId] = $campaign ? $campaign->key : (string)$campaignId;
                    }
                    $campaignTotals[$campaignId] = [
                        'campaign' => $campaignNameById[$campaignId],
                        'revenue' => 0.0,
                        'subscriptions' => 0,
                    ];
                }
                $campaignTotals[$campaignId]['revenue'] += $revenue;
                $campaignTotals[$campaignId]['subscriptions'] += $subscriptions;
            }

            uasort($campaignTotals, static function (array $campaignA, array $campaignB): int {
                return $campaignB['revenue'] <=> $campaignA['revenue'];
            });

            foreach ($campaignTotals as $totals) {
                if ($totals['revenue'] <= 0.0 && $totals['subscriptions'] <= 0) {
                    continue;
                }
                $entry = new DataSeriesEntry();
                $entry->addValue('date', $timeEntry->values['date'] ?? '');
                $entry->addValue('campaign', $totals['campaign']);
                $entry->addValue('revenue', $totals['revenue']);
                $entry->addValue('subscriptions', $totals['subscriptions']);
                $entries[] = $entry;
            }

            if ($totalRevenue > 0.0 || $totalSubscriptions > 0) {
                $totalEntry = new DataSeriesEntry();
                $totalEntry->addValue('date', $timeEntry->values['date'] ?? '');
                $totalEntry->addValue('campaign', 'All Campaigns');
                $totalEntry->addValue('revenue', $totalRevenue);
                $totalEntry->addValue('subscriptions', $totalSubscriptions);
                $entries[] = $totalEntry;
            }
        }

        return new DataSeries($columns, $entries);
    }

    /**
     * set the chart component
     * @return void
     */
    protected function prepareChart(): void
    {
        $dataSeriesInverted = new DataSeries($this->dataSeries->columns, array_reverse($this->dataSeries->entries));

        // if we are doing a stacked bar chart, let's remove the overall data bar, since it's redundant
        if ($this->chartType === 'bar' && $this->breakdown) {
            // remove the second element from the array $dataSeriesInverted->columns
            $newColumns = [];
            $seenStacks = [];
            foreach ($dataSeriesInverted->columns as $column) {
                if (in_array($column->stack, $seenStacks) || $column->key === 'date') {
                    $newColumns[] = $column;
                } else {
                    $seenStacks[] = $column->stack;
                }
            }
            $dataSeriesInverted->columns = $newColumns;
        }
        $columnKeys = [];
        foreach ($dataSeriesInverted->columns as $column) {
            if ($column->chart) {
                $columnKeys[] = $column->key;
            }
        }
        $this->chart = new DataSeriesChart([
            'dataSeries' => $dataSeriesInverted,
            'chartType' => $this->chartType,
            'labelColumnKey' => 'date',
            'dataSetColumnKeys' => $columnKeys
        ]);
        $this->chart->prepare();
    }
}
