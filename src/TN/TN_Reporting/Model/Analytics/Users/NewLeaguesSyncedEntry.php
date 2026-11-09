<?php

namespace TN\TN_Reporting\Model\Analytics\Users;

use FBG\FBG_FF\Model\UserDataModel\UserData\FFLeague;
use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Model\PersistentModel\Search\SearchArguments;
use TN\TN_Core\Model\PersistentModel\Search\SearchComparison;
use TN\TN_Core\Model\UserDataModel\Operation;
use TN\TN_Reporting\Model\Analytics\AnalyticsEntry;
use TN\TN_Reporting\Model\Analytics\DataSeries\AnalyticsDataSeriesColumn;

#[TableName('analytics_new_leagues_synced_entries')]
class NewLeaguesSyncedEntry extends AnalyticsEntry
{
    /** @var array|string[] */
    public static array $filters = [];

    public float $newLeaguesSynced = 0;

    public float $newDynastyLeaguesSynced = 0;

    public float $syncedLeagueUsers = 0;

    /**
     * @return void
     * @throws ValidationException
     */
    public function calculateDataAndUpdate(): void
    {
        echo 'updating new leagues synced report for ' . date('Y-m-d', $this->dayTs) . PHP_EOL;
        $endTs = strtotime('+1 day', $this->dayTs) - 1;
        $created = $this->countCreatedSyncedLeagues($endTs);
        $syncedUsers = FFLeague::searchDistinctRows(new SearchArguments(conditions: [
            new SearchComparison('`hostaccountId`', '!=', ''),
        ]), ['userId'], true);

        $this->update([
            'newLeaguesSynced' => $created['synced'],
            'newDynastyLeaguesSynced' => $created['dynasty'],
            'syncedLeagueUsers' => count($syncedUsers),
        ]);
    }

    /**
     * Synced leagues whose create operation falls on this day.
     * A synced league has a non-empty hostaccountId. Dynasty is the subset with dynasty set.
     *
     * @return array{synced: int, dynasty: int}
     */
    protected function countCreatedSyncedLeagues(int $endTs): array
    {
        $operations = Operation::search(new SearchArguments(conditions: [
            new SearchComparison('`model`', '=', FFLeague::getModelName()),
            new SearchComparison('`method`', '=', Operation::CREATE),
            new SearchComparison('`originTs`', '>=', $this->dayTs),
            new SearchComparison('`originTs`', '<=', $endTs),
        ]), true);

        $ids = [];
        foreach ($operations as $operation) {
            if ($operation->recordId > 0) {
                $ids[$operation->recordId] = $operation->recordId;
            }
        }

        $synced = 0;
        $dynasty = 0;
        if ($ids === []) {
            return ['synced' => 0, 'dynasty' => 0];
        }

        foreach (FFLeague::readFromIds(array_values($ids), true) as $league) {
            if ((string)$league->hostaccountId === '') {
                continue;
            }
            $synced++;
            if ($league->dynasty) {
                $dynasty++;
            }
        }

        return ['synced' => $synced, 'dynasty' => $dynasty];
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
        $newLeaguesSynced = 0;
        $newDynastyLeaguesSynced = 0;

        foreach ($dayReports as $dayReport) {
            $newLeaguesSynced += $dayReport->newLeaguesSynced;
            $newDynastyLeaguesSynced += $dayReport->newDynastyLeaguesSynced;
        }

        $latestDayReport = static::getLatestDayReport($dayReports);

        return [
            'newLeaguesSynced' => $newLeaguesSynced,
            'newDynastyLeaguesSynced' => $newDynastyLeaguesSynced,
            'syncedLeagueUsers' => $latestDayReport?->syncedLeagueUsers ?? 0,
        ];
    }

    public static function getBaseDataSeriesColumns(): array
    {
        $options = [
            'decimals' => 0,
            'chart' => true,
        ];

        return [
            new AnalyticsDataSeriesColumn('newLeaguesSynced', 'New leagues synced', array_merge($options, ['emphasize' => true])),
            new AnalyticsDataSeriesColumn('newDynastyLeaguesSynced', 'New dynasty leagues synced', $options),
            new AnalyticsDataSeriesColumn('syncedLeagueUsers', 'Unique users with a synced league', $options),
        ];
    }
}
