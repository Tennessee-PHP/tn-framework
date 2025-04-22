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
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Component\Analytics\Dashboard\DashboardComponent;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeries;

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
                $this->filterSelects['gateway']->selected = '';
                break;

            case 'campaign':
                if (!in_array('campaign', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'campaignId';
                $this->filterSelects['campaign']->selected = '';
                break;

            case 'plan':
                if (!in_array('plan', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'planKey';
                $this->filterSelects['plan']->selected = '';
                break;

            case 'billingcycle':
                if (!in_array('billingCycle', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'billingCycleKey';
                $this->filterSelects['billingCycle']->selected = '';
                break;
            case 'producttype':
                if (!in_array('productType', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'productTypeKey';
                $this->filterSelects['productType']->selected = '';
                break;
            case 'refundreason':
                if (!in_array('refundReason', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'refundReasonKey';
                $this->filterSelects['refundReason']->selected = '';
                break;
            case 'endedreason':
                if (!in_array('endedReason', $this->analyticsEntryClassName::$filters)) {
                    $this->breakdown = null;
                    break;
                }
                $this->breakdown = 'endedReasonKey';
                $this->filterSelects['endedReason']->selected = '';
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
            if ($filter === 'campaign') {
                $filters['campaignId'] = $select->selected->key;
            } else {
                $filters[$filter . 'Key'] = $select->selected->key;
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
        $this->table = new DataSeriesTable($this->dataSeries);
        $this->table->prepare();
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
