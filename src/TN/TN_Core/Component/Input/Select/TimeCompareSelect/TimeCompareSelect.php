<?php

namespace TN\TN_Core\Component\Input\Select\TimeCompareSelect;

use TN\TN_Core\Component\Input\Select\Option;
use TN\TN_Core\Component\Input\Select\Select;
use TN\TN_Core\Model\Time\Time;

/**
 * select a date range to compare to
 * @author Simon Shepherd
 */
class TimeCompareSelect extends Select
{
    public string $htmlClass = 'tn-component-select-timecompare-select';
    public string $requestKey = 'timecompare';

    protected function getOptions(): array
    {
        return [
            new Option('', 'None', null, false, true),
            new Option('30-day', '30 days earlier', null),
            new Option('1-year', '1 year earlier', null),
            new Option('2-year', '2 years earlier', null),
            new Option('3-year', '3 years earlier', null),
            new Option('1-season', '1 season earlier', null),
            new Option('2-season', '2 seasons earlier', null),
            new Option('3-season', '3 seasons earlier', null)
        ];
    }

    protected function getDefaultOption(): mixed
    {
        return $this->options[0];
    }

    public static function getReadable(string $key): string
    {
        $parts = explode('-', $key);
        $num = (int)$parts[0];
        $unit = (string)$parts[1] . 's';
        return "{$num} " . (($num === 1 ? rtrim($unit, 's') : $unit) . ' earlier');
    }

    public static function getNumberOfDaysDifference(string $key, int $fromTs): int
    {
        $parts = explode('-', $key);
        $num = (int)$parts[0];
        $unit = (string)$parts[1];
        if ($unit === 'day') {
            return $num;
        }
        if ($unit === 'year') {
            $diffTs = strtotime("-{$num} years", $fromTs);
            $diff = $fromTs - $diffTs;
            return round($diff / Time::ONE_DAY);
        }
        if ($unit === 'season') {
            // For TN version, treat seasons as years starting from September 1st
            $year = (int)date('Y', $fromTs);
            $month = (int)date('n', $fromTs);
            if ($month < 9) {
                $year--; // If before September, use previous year's season
            }
            $seasonStartTs = strtotime("September 1, {$year}");
            $prevSeasonStartTs = strtotime("September 1, " . ($year - $num));
            $diff = $seasonStartTs - $prevSeasonStartTs;

            return round($diff / Time::ONE_DAY);
        }
        return 0; // Default case for invalid units
    }
}
