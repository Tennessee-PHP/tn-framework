<?php

namespace TN\TN_Reporting\Component\Analytics\Dashboard\DashboardSummary;

use TN\TN_Core\Component\DataSeries\DataSeriesChart\DataSeriesChart;
use TN\TN_Core\Component\HTMLComponent;
use TN\TN_Core\Model\DataSeries\DataSeries;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeries;

abstract class TypeSummary extends HTMLComponent
{
    public string $template = 'TN_Reporting/Component/Analytics/Dashboard/DashboardSummary/TypeSummary.tpl';
    /** @var AnalyticsDataSeries */
    public AnalyticsDataSeries $dataSeries;

    /** @var DataSeriesChart */
    public DataSeriesChart $chart;

    /** @var DashboardDial */
    public DashboardDial $dial;

    public string $analyticsEntryClass = '';

    public string $chartType = 'line';

    public string $reportKey = '';

    public ?string $title;

    public string $compareMethod = '';

    public string $compareKey = '';

    public array $dialDisplayOptions = [
        'prefix' => '$',
        'decimals' => 2
    ];

    /**
     * @inheritDoc
     */
    public function prepare(): void
    {
        $this->prepareDataSeries();
        $this->prepareChart();
        $this->prepareDial();
    }

    protected function prepareDial(): void
    {
        // let's total the revenue for the last 30 days
        $this->dial = new DashboardDial(array_merge($this->getDialTotals(), $this->dialDisplayOptions));
        $this->dial->prepare();
    }

    protected function getDialTotals(): array
    {
        $totals = [
            'now' => 0,
            'year' => 0,
            'season' => 0
        ];

        $compareKey = $this->compareKey;

        foreach (array_reverse($this->dataSeries->entries) as $entry) {
            foreach ($entry->dayReportsByPrefix as $prefix => $dayReports) {
                $key = match($prefix) {
                    '1-year' => 'year',
                    '1-season' => 'season',
                    default => 'now'
                };
                foreach ($dayReports as $dayReport) {
                    if (isset($dayReport->$compareKey)) {
                        if ($this->compareMethod === 'total') {
                            $totals[$key] += $dayReport->$compareKey;
                        } elseif ($this->compareMethod === 'last') {
                            $totals[$key] = $dayReport->$compareKey;
                        }
                    }
                }
            }
        }

        return $totals;
    }

    /**
     * set the data series
     * @return void
     */
    protected function prepareDataSeries(): void
    {
        $this->dataSeries = new AnalyticsDataSeries($this->analyticsEntryClass, 'day',
            strtotime('-30 days', Time::getTodayTs()), Time::getTodayTs(), [], null, ['1-year', '1-season']);
    }

    /**
     * set the chart component
     * @return void
     */
    protected function prepareChart(): void
    {
        $dataSeriesInverted = new DataSeries($this->dataSeries->columns, array_reverse($this->dataSeries->entries));
        $columnKeys = [];
        foreach ($dataSeriesInverted->columns as $column) {
            if ($column->chart && !str_contains($column->key, '1-')) {
                $columnKeys[] = $column->key;
            }
        }
        $this->chart = new DataSeriesChart([
            'dataSeries' => $dataSeriesInverted,
            'chartType' => $this->chartType,
            'labelColumnKey' => 'date',
            'dataSetColumnKeys' => $columnKeys,
            'stackBars' => false,
            'showLegend' => false
        ]);
        $this->chart->prepare();
    }
}