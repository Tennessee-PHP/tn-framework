<?php

namespace TN\TN_Reporting\Model\Analytics\DataSeries;

use TN\TN_Core\Component\Input\Select\TimeCompareSelect\TimeCompareSelect;
use TN\TN_Core\Model\DataSeries\DataSeries;
use TN\TN_Core\Model\DataSeries\DataSeriesColumn;
use TN\TN_Core\Model\Package\Stack;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\Time\Time;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;

class AnalyticsDataSeries extends DataSeries
{

    /** @var AnalyticsDataSeriesTimeEntry[] */
    public array $entries;

    /** @var array the base day reports */
    public array $dayReports;

    public array $comparisonDayReports;

    public array $comparisons;

    public function __construct(
        /** @var string */
        public string $dayReportClass,

        /** @var string */
        public string  $timeUnit,

        /** @var int */
        public int     $startTs,

        /** @var int */
        public int     $endTs,

        /** @var array */
        public array   $filters = [],

        /** @var null|string */
        public ?string $breakdown = null,

        /** @var null|string|array */
        public string|array|null $compareTo = null
    ) {
        // get all the day reports that fall inside the range
        $this->dayReports = $this->getAllReports($this->startTs, $this->endTs);

        if (!$this->compareTo) {
            $this->compareTo = [];
        }
        if (is_string($this->compareTo)) {
            $this->compareTo = [$this->compareTo];
        }

        $this->comparisonDayReports = [];
        $this->comparisons = [];
        foreach ($this->compareTo as $compareTo) {
            $comparisonDays = TimeCompareSelect::getNumberOfDaysDifference($compareTo, $this->startTs);
            $comparisonStartTs = strtotime("-{$comparisonDays} days", $this->startTs);
            $comparisonEndTs = strtotime("-{$comparisonDays} days", $this->endTs);
            $comparisonEntries = self::getAllReports($comparisonStartTs, $comparisonEndTs);
            $this->comparisonDayReports[$compareTo] = $comparisonEntries;
            $this->comparisons[$compareTo] = [
                'key' => $compareTo,
                'days' => $comparisonDays
            ];
        }

        // use something on base day reports to group them according to time unit
        $this->groupDayReportsIntoEntries();

        // now go through each, changing the set of day reports into an entry in the data series
        foreach ($this->entries as $entry) {
            $entry->extractValuesFromDayReports($this->breakdown);
        }

        $baseColumns = $this->dayReportClass::getBaseDataSeriesColumns();
        $prefixes = [];
        foreach ($this->entries as $entry) {
            $prefixes = array_merge($prefixes, array_keys($entry->dayReportsByPrefix));
        }
        $prefixes = array_unique($prefixes);

        $this->columns = [];
        $this->columns[] = new DataSeriesColumn('date', 'Date');
        foreach ($prefixes as $prefix) {
            if ($prefix === 'all') {
                $prefix = '';
            }
            foreach ($baseColumns as $column) {
                $clonedColumn = clone $column;
                $clonedColumn->key = $prefix . $column->key;
                if (!empty($prefix)) {
                    $clonedColumn->adjustLabelForPrefix($prefix, $this->comparisons, $this->breakdown);
                }
                $this->columns[] = $clonedColumn;
            }
        }

        parent::__construct($this->columns, $this->entries);
    }

    /**
     * @param AnalyticsEntry[] $dayReports
     * @param AnalyticsEntry[] $comparisonDayReports
     * @param int $comparisonDays
     */
    protected function groupDayReportsIntoEntries(): void
    {
        // first let's create our time entries based on unit, start ts, end ts
        // first, let's see if we need to wind back the startTs to the start of its time unit
        $this->startTs = Time::moveTsBackToStartOfUnit($this->timeUnit, $this->startTs);
        $this->endTs = Time::moveTsForwardToEndOfUnit($this->timeUnit, $this->endTs);
        $this->entries = [];

        $ts = $this->startTs;
        while ($ts < $this->endTs) {
            $startEntry = $ts;
            $ts = Time::moveTsForwardToEndOfUnit($this->timeUnit, $ts);
            $endEntry = $ts;

            // this class will have a startTs, endTs, a label (auto-generated) and a set of day reports
            $this->entries[] = new AnalyticsDataSeriesTimeEntry($this->timeUnit, $startEntry, $endEntry);

            // now add one day to ts for the next unit
            $ts = strtotime('+1 day', $ts);
        }

        // iterate through each day report, adding it into a time entry based on its timestamp
        foreach ($this->dayReports as $dayReport) {
            $this->addAnalyticsEntryToTimeEntry($dayReport);
        }

        if (!empty($this->comparisonDayReports)) {
            foreach ($this->comparisonDayReports as $key => $comparisonDayReports) {
                $comparisonDays = $this->comparisons[$key]['days'];
                foreach ($comparisonDayReports as $comparisonDayReport) {
                    $this->addAnalyticsEntryToTimeEntry($comparisonDayReport, $key, $comparisonDays);
                }
            }
        }

        $this->entries = array_reverse($this->entries);
    }

    /**
     * @param AnalyticsEntry $dayReport
     * @param string|null $comparisonKey
     * @param int $comparisonDays
     * @return void
     */
    protected function addAnalyticsEntryToTimeEntry(AnalyticsEntry $dayReport, ?string $comparisonKey = null, int $comparisonDays = 0): void
    {
        $dayTs = $dayReport->dayTs;
        if ($comparisonKey) {
            $dayTs = strtotime("+{$comparisonDays} days", $dayTs);
        }
        foreach ($this->entries as $entry) {
            if ($dayTs >= $entry->startTs && $dayTs <= $entry->endTs) {
                $entry->addDayReport($dayReport, $comparisonKey);
                break;
            }
        }
    }

    /**
     * @return AnalyticsEntry[]
     */
    protected function getAllReports(int $startTs, int $endTs): array
    {
        $conditions = [];
        $conditions[] = new SearchComparison('`dayTs`', '>=', $startTs);
        $conditions[] = new SearchComparison('`dayTs`', '<=', $endTs);
        foreach ($this->dayReportClass::$filters as $filter) {
            if ($filter === 'campaign') {
                $filterKey = 'campaignId';
            } else {
                $filterKey = $filter . 'Key';
            }
            if ($filterKey !== $this->breakdown) {
                $filterValue = $this->filters[$filterKey] ?? '';
                // When "All" is selected (empty value), skip this filter so we include all rows.
                // Otherwise e.g. campaignId = '' becomes campaign_id = 0 in MySQL and matches nothing.
                if ($filterValue !== '' && $filterValue !== null) {
                    $conditions[] = new SearchComparison("`{$filterKey}`", '=', $filterValue);
                }
            }
        }
        return $this->dayReportClass::search(new SearchArguments($conditions));
    }
}
