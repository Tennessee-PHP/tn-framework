<?php

namespace TN\TN_Core\Model\Time;

use TN\TN_Core\Error\TNException;
use TN\TN_Core\Model\Request\HTTPRequest;

/**
 * These are some static functions that handle time
 * 
 */
class Time
{
    const int ONE_MINUTE = 60;
    const int ONE_HOUR = 3600;
    const int ONE_DAY = 86400;
    const int ONE_YEAR = 86400 * 365;
    const int ONE_WEEK = 86400 * 7;
    const int ONE_MONTH = 86400 * 31;

    private static int $fixedTime = 0;

    /**
     * private method to get the current time.
     *
     * This allows overriding via a get parameter, '_testdate', but only on non-production environments.
     * @example try http://localhost:8080/?_testdate=2021-09-20 and http://localhost:8080/?_testdate=2021-08-20 and see
     * the tools menu change.
     * @return int
     */
    public static function getNow(): int
    {
        try {
            $request = HTTPRequest::get();
            if ($request) {
                $testDate = $request->getQuery('_testdate');
                if (!empty($testDate) && $_ENV['ENV'] !== 'production') {
                    return strtotime($testDate);
                }
            }
        } catch (TNException $e) {
            // do nothing
        }

        if (self::$fixedTime > 0) {
            return self::$fixedTime;
        }
        return time();
    }

    /**
     * returns the timestamp for midnight today
     * @return int
     */
    public static function getTodayTs(): int
    {
        return strtotime(date('Y-m-d 00:00:00', self::getNow()));
    }

    /**
     * fix the time to a non-moving integer. this is to help with tests. don't use in non-test code!
     * @param int $time
     */
    public static function fixTime(int $time): void
    {
        self::$fixedTime = $time;
    }

    /**
     * remove the fixed time
     */
    public static function resetFixTime(): void
    {
        self::$fixedTime = 0;
    }

    /**
     * returns an array [day, hours, minutes, seconds] of time between two unix timestamps
     * negative values are converted to 0
     * @param int $startTime
     * @param int $endTime
     * @return array
     */
    public static function getTsDifference(int $startTime, int $endTime): array
    {
        $difference = $endTime - $startTime;
        // if $difference is negative simply return an array of 0's
        if ($difference <= 0) {
            return ['days' => 0, 'hours' => 0, 'minutes' => 0, 'seconds' => 0];
        }

        $days = floor($difference / (24 * 60 * 60));
        $hours = floor(($difference - ($days * 24 * 60 * 60)) / (60 * 60));
        $minutes = floor(($difference - ($days * 24 * 60 * 60) - ($hours * 60 * 60)) / 60);
        $seconds = ($difference - ($days * 24 * 60 * 60) - ($hours * 60 * 60) - ($minutes * 60)) % 60;
        return ['days' => $days, 'hours' => $hours, 'minutes' => $minutes, 'seconds' => $seconds];
    }

    /**
     * @param string $unit
     * @param int $ts
     * @return int
     */
    public static function moveTsBackToStartOfUnit(string $unit, int $ts): int
    {
        if ($unit === 'week') {
            // wind back to sunday
            while ((int)date('w', $ts) > 0) {
                $ts = strtotime('-1 day', $ts);
            }
            return $ts;
        } else if ($unit === 'month') {
            // wind back to the first of the month
            return strtotime(date('Y-m-01', $ts));
        } else if ($unit === 'year') {
            // wind back to the first of the year
            return strtotime(date('Y-01-01', $ts));
        } else {
            // unit was day
            return $ts;
        }
    }

    /**
     * @param string $unit
     * @param int $ts
     * @return int
     */
    public static function moveTsForwardToEndOfUnit(string $unit, int $ts): int
    {
        if ($unit === 'week') {
            // wind back to sunday
            while ((int)date('w', $ts) < 6) {
                $ts = strtotime('+1 day', $ts);
            }
            return $ts;
        } else if ($unit === 'month') {
            // wind forward to the first of the next month
            $month = (int)date('m', $ts);
            $year = (int)date('Y', $ts);
            $month += 1;
            if ($month > 12) {
                $month = 1;
                $year += 1;
            }
            return strtotime('-1 day', strtotime($year . '-' . $month . '-01 00:00:00'));
        } else if ($unit === 'year') {
            // wind forwards to the last day of the current year
            $year = (int)date('Y', $ts);
            return strtotime('-1 day', strtotime(($year + 1) . '-01-01 00:00:00'));
        } else {
            // unit was day
            return $ts;
        }
    }
}
