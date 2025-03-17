<?php

namespace TN\TN_Reporting\Model\Analytics\DataSeries;

use TN\TN_Core\Model\DataSeries\DataSeriesTimeEntry;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;

/**
 * let's extend the data series time entry to make gathering day report data together more object-oriented
 * 
 */
class AnalyticsDataSeriesTimeEntry extends DataSeriesTimeEntry
{
    /** @var AnalyticsEntry[] */
    public array $dayReports = [];

    /** @var array */
    public array $comparisonDayReports = [];

    /** @var array  */
    public array $dayReportsByPrefix = [];

    /**
     * @param AnalyticsEntry $dayReport
     * @param string|null $comparisonKey
     * @return void
     */
    public function addDayReport(AnalyticsEntry $dayReport, ?string $comparisonKey = null): void
    {
        if ($comparisonKey) {
            if (!isset($this->comparisonDayReports[$comparisonKey])) {
                $this->comparisonDayReports[$comparisonKey] = [];
            }
            $this->comparisonDayReports[$comparisonKey][] = $dayReport;
        } else {
            $this->dayReports[] = $dayReport;
        }
    }

    /**
     * @param string|null $breakdown
     * @param bool $comparison
     * @return void
     */
    public function extractValuesFromDayReports(?string $breakdown): void
    {
        $this->buildDayReportsByPrefix($breakdown);
        $this->extractValuesFromDayReportsByPrefix();
    }

    /**
     * @return void
     */
    protected function extractValuesFromDayReportsByPrefix(): void
    {
        $this->addValue('date', $this->getLabel());
        foreach ($this->dayReportsByPrefix as $prefix => $dayReports) {
            if (empty($dayReports)) {
                continue;
            }
            $class = get_class($dayReports[0]);
            foreach ($class::getValuesFromDayReports($dayReports) as $key => $value) {
                if ($prefix === 'all') {
                    $prefix = '';
                }
                $this->addValue($prefix . $key, $value);
            }
        }
    }

    /**
     * @param string|null $breakdown
     * @return void
     */
    protected function buildDayReportsByPrefix(?string $breakdown): void
    {
        $this->buildDayReportsByPrefixFromDayReports('', $this->dayReports, $breakdown);
        if (!empty($this->comparisonDayReports)) {
            foreach ($this->comparisonDayReports as $key => $comparisonDayReports) {
                $this->buildDayReportsByPrefixFromDayReports($key, $comparisonDayReports, $breakdown);
            }

        }
    }

    /**
     * @param string $prefix
     * @param array $dayReports
     * @param string|null $breakdown
     * @return void
     */
    protected function buildDayReportsByPrefixFromDayReports(string $prefix, array $dayReports, ?string $breakdown): void
    {
        if ($breakdown) {
            foreach ($dayReports as $dayReport) {
                if (!isset($this->dayReportsByPrefix[$prefix . ':' . $dayReport->$breakdown])) {
                    $this->dayReportsByPrefix[$prefix . ':' . $dayReport->$breakdown] = [];
                }
                $this->dayReportsByPrefix[$prefix . ':' . $dayReport->$breakdown][] = $dayReport;
            }
        } else {
            $this->dayReportsByPrefix[empty($prefix) ? 'all' : $prefix] = $dayReports;
        }
    }
}