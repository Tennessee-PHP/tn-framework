<?php

namespace TN\TN_Reporting\Model\Analytics\Users;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\User\User;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_users_registrations_entries')]
class UsersRegistrationsEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = [];

    /** @var float */
    public float $userRegistrations = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating user registrations report for ' . date('Y-m-d', $this->dayTs) . PHP_EOL;
        $endTs = strtotime('+1 day', $this->dayTs) - 1;
        $userCount = User::count(new SearchArguments(conditions: [
            new SearchComparison('`createdTs`', '>=', $this->dayTs),
            new SearchComparison('`createdTs`', '<=', $endTs)
        ]));
        $this->update([
            'userRegistrations' => $userCount
        ]);
    }

    /**
     * @return array
     */
    public static function getFilterValues(): array
    {
        return [];
    }

    public static function getValuesFromDayReports(array $dayReports): array
    {
        $total = 0;

        foreach ($dayReports as $dayReport) {
            // add each day's churn to the total
            $total += $dayReport->userRegistrations;
        }

        // now add the aggregate of all the values as a value on the data series entry
        return [
            'userRegistrations' => $total
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0
        ];

        return [
            new AnalyticsDataSeriesColumn('userRegistrations', 'Users Registrations', array_merge($options, ['emphasize' => true, 'chart' => true]))
        ];
    }
}
