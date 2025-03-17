<?php

namespace TN\TN_Reporting\Model\Analytics\EmailList;
use Curl\Curl;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

class EmailListConvertKitTypeEntry extends AnalyticsEntry
{
    /** @var int */
    public int $count = 0;

    public static string $type = '';

    // or cancellations, new_subscribers
    public static string $apiProperty = '';

    protected static function getFilterValues(): array
    {
        return [];
    }

    public function calculateDataAndUpdate(): void
    {
        echo 'updating email list convert kit active report for ' . date('Y-m-d', $this->dayTs) . PHP_EOL;
        $curl = new Curl();
        $curl->setOpt(CURLOPT_FOLLOWLOCATION, 1);
        $curl->setOpt(CURLOPT_RETURNTRANSFER, true);
        $curl->setHeader('X-Kit-Api-Key', $_ENV['CONVERTKIT_V4_KEY']);
        $curl->setHeader('Accept', 'application/json');
        $response = $curl->get('https://api.kit.com/v4/account/growth_stats', [
            'starting' => date('Y-m-d', $this->dayTs),
            'ending' => date('Y-m-d', $this->dayTs)
        ]);

        if ($response->error) {
            return;
        }

        $apiProperty = static::$apiProperty;
        $response = json_decode($response->response);

        $this->update([
            'count' => abs($response->stats->$apiProperty)
        ]);
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0
        ];

        return [
            new AnalyticsDataSeriesColumn('count', ucfirst(static::$type) . ' Subscribers', array_merge($options, ['emphasize' => true, 'chart' => true]))
        ];
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $count = 0;

        foreach ($dayReports as $dayReport) {
            $count += $dayReport->count;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'count' => $count
        ];
    }
}