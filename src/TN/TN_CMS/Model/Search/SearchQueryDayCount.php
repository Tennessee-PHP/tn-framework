<?php

namespace TN\TN_CMS\Model\Search;

use TN\TN_Core\Attribute\MySQL\TableName;
use TN\TN_Core\Error\ValidationException;
use TN\TN_Core\Interface\Persistence;
use TN\TN_Core\Model\PersistentModel\PersistentModel;
use TN\TN_Core\Model\PersistentModel\Storage\MySQL\MySQL;
use TN\TN_Core\Model\Time\Time;

#[TableName('cms_search_query_day_counts')]
class SearchQueryDayCount implements Persistence
{
    use MySQL;
    use PersistentModel;

    public int $searchQueryId;
    public int $dayTs;
    public int $count;

    /**
     * records a search count for the given query id against today's timestamp
     * @param int $searchQueryId
     * @return void
     * @throws ValidationException
     */
    public static function recordSearch(int $searchQueryId): void
    {
        // let's get the current day's timestamp
        $dayTs = Time::getTodayTs();

        // let's get it, if we already have one
        $records = self::searchByProperties([
            'searchQueryId' => $searchQueryId,
            'dayTs' => $dayTs
        ]);

        if (count($records) > 0) {
            $record = $records[0];
            $update = [
                'count' => $record->count + 1
            ];
        } else {
            $record = self::getInstance();
            $update = [
                'searchQueryId' => $searchQueryId,
                'dayTs' => $dayTs,
                'count' => 1
            ];
        }

        $record->update($update);
    }
}