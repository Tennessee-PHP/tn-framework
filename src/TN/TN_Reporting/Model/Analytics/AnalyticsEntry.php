<?php

namespace TN\TN_Reporting\Model\Analytics;

use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\DataSeries\DataSeriesColumn;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

abstract class AnalyticsEntry implements Persistence
{
    use MySQL;
    use PersistentModel;

    /** @var array|string[] */
    public static array $filters = [];

    /** @var int timestamp of midnight on the day */
    public int $dayTs;

    public static function updateTodayReports(): void
    {
        self::updateDayReports(Time::getTodayTs());
    }

    public static function updateDayReports(int $ts): void
    {
        foreach (static::getDayReports($ts) as $report) {
            $report->calculateDataAndUpdate();
        }
    }

    /**
     * @return string
     */
    protected function getFilterIndexKey(): string
    {
        $filters = get_called_class()::$filters;
        $keys = [];
        foreach ($filters as $filter) {
            $prop = $filter . 'Key';
            $keys[] = $this->$prop ?? '';
        }
        return implode(':', $keys);
    }

    /**
     * @return array
     */
    protected static abstract function getFilterValues(): array;

    /**
     * @param int $ts
     * @return array
     * @throws ValidationException
     */
    public static function getDayReports(int $ts): array
    {
        $startDayTs = strtotime(date('Y-m-d 00:00:00', $ts));
        // read all the reports for today and index them by gateway, plan and billing cycle
        $reports = self::searchByProperty('dayTs', $startDayTs);

        // index by all the filters
        $indexedReports = [];
        foreach ($reports as $report) {
            $indexKey = '';
            foreach (get_called_class()::$filters as $filter) {
                $indexKey .= $report->{$filter . 'Key'} . ':';
            }
            if (empty($indexKey)) {
                $indexKey = 'all';
            }
            $indexedReports[$indexKey] = $report;
        }

        $filterValues = get_called_class()::getFilterValues();
        if (empty($filterValues)) {
            $indexKey = 'all';
            if (!isset($indexedReports[$indexKey])) {
                $report = self::getInstance();
                $report->update(['dayTs' => $startDayTs]);
                $reports[] = $report;
            }
        } else {
            $variants = self::buildDayReportVariants([], $filterValues);
            foreach ($variants as $data) {
                $indexKey = '';
                foreach (get_called_class()::$filters as $filter) {
                    $indexKey .= $data[$filter] . ':';
                }
                if (!isset($indexedReports[$indexKey])) {
                    $report = self::getInstance();
                    $report->update(array_merge($data, ['dayTs' => $startDayTs]));
                    $reports[] = $report;
                }
            }
        }

        return $reports;

    }

    protected static function buildDayReportVariants(array $base, array $filterValues): array
    {
        $variants = [];
        $keys = array_keys($filterValues);
        $key = array_shift($keys);
        $values = $filterValues[$key];
        unset($filterValues[$key]);
        foreach ($values as $value) {
            $variant = $base;
            $variant[$key] = $value;
            if (empty($keys)) {
                $variants[] = $variant;
            } else {
                $variants = array_merge($variants, self::buildDayReportVariants($variant, $filterValues));
            }
        }
        return $variants;
    }

    /**
     * given a report, get updated data, and save it to the database
     * @return void
     */
    public abstract function calculateDataAndUpdate(): void;

    /**
     * @return DataSeriesColumn[]
     */
    public abstract static function getBaseDataSeriesColumns(): array;

    /**
     * @param AnalyticsEntry[] $dayReports
     * @return array
     */
    public abstract static function getValuesFromDayReports(array $dayReports): array;
}